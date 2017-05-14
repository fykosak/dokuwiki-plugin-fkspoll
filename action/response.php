<?php

class action_plugin_fkspoll_response extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_fkspoll
     */
    private $helper;

    public function __construct() {
        $this->helper = $this->loadHelper('fkspoll');
    }

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'response');
    }


    public function response(Doku_Event &$event) {
        global $INPUT;
        if ($INPUT->str('target') !== helper_plugin_fkspoll::TARGET) {
            return;
        }
        if ($INPUT->param('poll')['do'] !== 'vote') {
            return;
        }
        $event->preventDefault();
        $event->stopPropagation();

        if (getSecurityToken() != $INPUT->str('sectok')) {
            return;
        }
        $question_id = (int)$INPUT->param('poll')['question-id'];
        if (!$this->helper->isActualQuestion($question_id)) {
            msg('neplatná anketa');
            return;
        }
        if (!$this->helper->hasVoted($question_id)) {
            $answers = $INPUT->param('answer');
            if (($INPUT->int('type') == 1) && ($answers['id'][0] != 0)) {
                unset($answers['text']);
            }
            if (isset($answers['id'])) {
                foreach ($answers['id'] as $id) {
                    $this->saveResponse($question_id, $id);
                }
            }
            if (isset($answers['text'])) {
                foreach ($answers['text'] as $text) {
                    $text = trim($text);
                    if ($text == '') {
                        continue;
                    }
                    $id = $this->helper->createAnswer($question_id, $text);
                    $this->saveResponse($question_id, $id);
                }
            }
            setcookie('poll-' . $question_id, 1, time() + 60 * 60 * 24 * 100);
            $_COOKIE['poll-' . $question_id] = 1;

            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            msg($this->getLang('already_voted'), -1);
            return;
        }
    }

    private function saveResponse($question_id, $id) {
        $this->helper->sqlite->query('INSERT INTO ' . helper_plugin_fkspoll::db_table_response . ' 
            (question_id,answer_id,users_id,remote_addr,remote_host,user_agent,accept,accept_language,referer,`from`,cookie,inserted) 
            VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',
            $question_id,
            $id,
            @$_SESSION['id'],
            @$_SERVER['REMOTE_ADDR'],
            @$_SERVER['REMOTE_HOST'],
            @$_SERVER['HTTP_USER_AGENT'],
            @$_SERVER['HTTP_ACCEPT'],
            @$_SERVER['HTTP_ACCEPT_LANGUAGE'],
            @$_SERVER['HTTP_REFERER'],
            $_SERVER['HTTP_FROM'],
            serialize($_COOKIE),
            \time());
    }

}
