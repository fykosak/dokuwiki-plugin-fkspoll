<?php

use dokuwiki\Form\Form;

require_once(__DIR__ . '/form/checkable-element.php');

class helper_plugin_fkspoll extends DokuWiki_Plugin {

    const db_table_answer = 'poll_answer';
    const db_table_question = 'poll_question';
    const db_table_response = 'poll_response';

    const TARGET = 'fks-poll';

    /**
     *
     * @var helper_plugin_sqlite
     */
    public $sqlite;

    public function __construct() {
        $this->sqlite = $this->loadHelper('sqlite', false);
        $pluginName = $this->getPluginName();
        if (!$this->sqlite) {
            msg($pluginName . ': This plugin requires the sqlite plugin. Please install it.');
            return;
        }
        if (!$this->sqlite->init('fkspoll', DOKU_PLUGIN . $pluginName . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR)) {
            msg($pluginName . ': Cannot initialize database.');
            return;
        }
    }

    public function hasVoted($question_id) {
        return isset($_COOKIE['poll-' . $question_id]) && $_COOKIE['poll-' . $question_id];
    }

    public function saveResponse($question_id, $id) {
        $this->sqlite->query('INSERT INTO ' . self::db_table_response . ' 
            (question_id,answer_id,users_id,remote_addr,remote_host,user_agent,accept,accept_language,referer,`from`,cookie,inserted) 
            VALUES(?,?,?,?,?,?,?,?,?,?,?,?)', $question_id, $id, @$_SESSION['id'], @$_SERVER['REMOTE_ADDR'], @$_SERVER['REMOTE_HOST'], @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_ACCEPT'], @$_SERVER['HTTP_ACCEPT_LANGUAGE'], @$_SERVER['HTTP_REFERER'], $_SERVER['HTTP_FROM'], serialize($_COOKIE), \time());
    }

    public function createAnswers($id, $answers) {
        foreach ($answers as $answer) {
            $this->createAnswer($id, $answer);
        }
    }

    public function editAnswers($questionID, $answers) {
        foreach ($answers as $id => $answer) {
            $this->editAnswer($questionID, $id, $answer);
        }
    }

    public function editAnswer($question_id, $id, $answer) {
        if (trim($answer)) {
            $this->sqlite->query('UPDATE ' . self::db_table_answer . ' 
                    SET answer=?
                    WHERE answer_id=? ', $answer, $id);
        } else {
            $this->sqlite->query('DELETE FROM ' . self::db_table_answer . '                     
                    WHERE answer_id=? ', $id);
        }
    }


    public function createAnswer($question_id, $text) {
        if (!$this->answer2ID($question_id, $text)) {
            $this->sqlite->query('INSERT INTO ' . self::db_table_answer . ' 
                    (question_id,answer) 
                    VALUES(?,?)', $question_id, $text);
        }
        return $this->answer2ID($question_id, $text);
    }

    public function answer2ID($question_id, $text) {

        $res = $this->sqlite->query('SELECT answer_id FROM ' . self::db_table_answer . ' WHERE question_id=? AND answer=?', $question_id, $text);
        return $this->sqlite->res2single($res);
    }

    public function getResponses($poll) {
        $sql = 'SELECT answer,answer_id
            FROM ' . self::db_table_answer . '            
            WHERE question_id = ?';
        $res = $this->sqlite->query($sql, $poll['question_id']);
        $ans = $this->sqlite->res2arr($res);
        $response = array();
        $sum = 0;
        foreach ($ans as $an) {
            $res = $this->sqlite->query('SELECT COUNT(*) AS count
            FROM ' . self::db_table_response . '        
            WHERE answer_id=? AND question_id=?
            GROUP BY answer_id', $an['answer_id'], $poll['question_id']);
            $c = $this->sqlite->res2single($res);
            $sum += $c;
            $response[] = ['count' => $c, 'answer' => $an['answer']];
        }
        $r = array();
        foreach ($response as $k => $answer) {
            $r[$k]['answer'] = $answer['answer'];
            $r[$k]['abs'] = $answer['count'] ? $answer['count'] : 0;
            $r[$k]['rel'] = $sum > 0 ? $answer['count'] / $sum : 0;
            $r[$k]['per'] = round(100 * $r[$k]['rel']);
            $v = $r[$k]['abs'];
            if ($v == 1) {
                $l = $this->getLang('N-SG_vote');
            } elseif ($v > 0 && $v < 5) {
                $l = $this->getLang('N-PL_vote');
            } else {
                $l = $this->getLang('G-PL_vote');
            }
            $r[$k]['unit'] = $l;
        }

        return $r;
    }

    public function getCurrentPolls($lang = 'cs') {
        $allPolls = $this->allPolls($lang);
        $polls = [];
        foreach ($allPolls as $poll) {
            if ($this->isActualQuestion($poll['question_id'])) {
                $poll['answers'] = $this->getAnswers($poll['question_id']);
                $polls[] = $poll;
            }
        }
        return $polls;
    }

    public function getPollByID($id) {
        $sql = 'SELECT * FROM ' . self::db_table_question . ' WHERE question_id = ?';
        $res = $this->sqlite->query($sql, $id);
        foreach ($this->sqlite->res2arr($res) as $poll) {
            $poll['answers'] = $this->getAnswers($poll['question_id']);
            return $poll;
        }
        return null;
    }

    public function getAnswers($id) {
        $res = $this->sqlite->query('SELECT * FROM ' . self::db_table_answer . '
            WHERE question_id=?', $id);
        return $this->sqlite->res2arr($res);
    }

    public function renderPoll($poll, $type) {
        if ($this->hasVoted($poll['question_id'])) {
            return $this->getClosedPollHtml($poll, $type);
        } else {
            return $this->getOpenPollHtml($poll, $type);
        }
    }

    public function getClosedPollHtml($poll, $type) {
        $html = '';
        $poll['responses'] = $this->getResponses($poll);
        $max = max(array_map(function ($d) {
            return $d['per'];
        }, $poll['responses']));


        $html .= $this->getPollHeadline($poll, $type);
        $html .= '<div class="card-block">';
        $html .= $this->getPollDuration($poll);
        foreach ($poll['responses'] as $key => $response) {
            $html .= '<p>';
            // $html .= '<div>';
            $html .= '<span>' . htmlspecialchars($response['answer']) . '</span>';
            $html .= ' <small class="text-muted">' . $response['abs'] . ' ' . $response['unit'] . ' (' . $response['per'] . '%)' . '</small>';
            // $html .= '</div>';
            $percent = (float)($response['per'] * 100 / ($max ? $max : 1));

            $html .= '<div class="progress">';
            $html .= '<div class="progress-bar" role="progressbar" style="width: ' . $percent . '% " aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . number_format($percent, 2, ',', '') . '%</div>';
            $html .= '</div>';
            $html .= '</p>';
        }
        $html .= '</div>';
        return $html;
    }

    private function getPollDuration($poll) {
        $dateFrom = date($this->getLang('date_format'), strtotime($poll['valid_from']));
        $dateTo = date($this->getLang('date_format'), strtotime($poll['valid_to']));
        return '<small class="card-subtitle text-muted">' . $dateFrom . ' - ' . $dateTo . '</small>';
    }

    private function getPollHeadline($poll, $type) {
        return '<div class="card-header card-inverse">' . htmlspecialchars($poll['question']) . '</div>';
    }

    private function getOpenPollHtml($poll, $type) {
        $html = '';
        if (!$poll) {
            return '';
        }

        $html .= $this->getPollHeadline($poll, $type);
        $html .= '<div class="card-block">';
        $form = new Form();
        $form->setHiddenField('target', self::TARGET);
        $form->setHiddenField('poll[do]', 'vote');
        $form->setHiddenField('poll[question-id]', $poll['question_id']);
        $form->addTagOpen('div')
            ->addClass('form-group');
        if ($poll['type'] == 1) {
            $this->addSinglePollFields($form, $poll);
        } elseif ($poll['type'] == 2) {
            $this->addMultiPollFields($form, $poll);
        }
        $form->addTagClose('div');

        if ($poll['new_answer'] == 1) {
            $form->addTagOpen('div')
                ->addClass('form-group');
            $this->addNewAnswerField($form, $poll);
            $form->addTagClose('div');
        }

        $form->addButton('submit', $this->getLang('send_answer'))
            ->addClass('btn btn-success');

        $html .= $form->toHTML();
        $html .= '</div>';
        return $html;
    }

    private function addNewAnswerField(Form &$form, $poll) {
        if ($poll['type'] == 1) {
        }
        $form->addTextInput('answer[text][]', null)
            ->attr('placeholder', $this->getLang('another_answer'))
            ->addClass('form-control');
    }

    private function addSinglePollFields(Form &$form, $poll) {
        foreach ($poll['answers'] as $answer) {
            $form->addTagOpen('div')
                ->addClass('form-check');
            $radio = new \fks\Form\CheckableElement('radio', 'answer[id][]', $answer['answer']);
            $radio->val($answer['answer_id']);
            $form->addElement($radio);
            $form->addTagClose('div');
        }
    }

    private function addMultiPollFields(Form &$form, $poll) {
        foreach ($poll['answers'] as $answer) {
            $form->addTagOpen('div')
                ->addClass('form-check');
            $checkbox = new \fks\Form\CheckableElement('checkbox', 'answer[id][]', $answer['answer']);
            $checkbox->val($answer['answer_id']);
            $form->addElement($checkbox);;
            $form->addTagClose('div');
        }
    }

    public function createQuestion($p) {
        $this->sqlite->query('INSERT INTO ' . self::db_table_question . ' 
                (question,lang,valid_from,valid_to,type,new_answer,sectok) 
                VALUES(?,?,?,?,?,?,?)', $p['question'], $p['lang'], $p['valid_from'], $p['valid_to'], $p['type'], $p['new_answer'], $p['sectok']);
        $res = $this->sqlite->query('SELECT max(question_id) FROM ' . self::db_table_question);
        return $this->sqlite->res2single($res);
    }

    public function editQuestion($p) {
        $this->sqlite->query('UPDATE ' . self::db_table_question . ' 
                SET question=?,valid_from=?,valid_to=?,type=?,new_answer=?,sectok=?            
                WHERE question_id = ?', $p['question'], $p['valid_from'], $p['valid_to'], $p['type'], $p['new_answer'], $p['sectok'], $p['question_id']);
        $res = $this->sqlite->query('SELECT max(question_id) FROM ' . self::db_table_question);
        return $this->sqlite->res2single($res);
    }

    public function allPolls($lang = 'cs') {
        $sql = 'SELECT * FROM ' . self::db_table_question . ' WHERE lang = ?';
        $res = $this->sqlite->query($sql, $lang);
        return $this->sqlite->res2arr($res);
    }

    public function isNewQuestion($sectok) {
        $res = $this->sqlite->query('SELECT * FROM ' . self::db_table_question . ' WHERE sectok=? ', $sectok);
        $ar = $this->sqlite->res2arr($res);
        return (empty($ar) ? 1 : 0);
    }

    public function isActualQuestion($id) {
        $poll = $this->getPollByID($id);
        if (strtotime($poll['valid_from']) < time() && (strtotime($poll['valid_to']) + 60 * 60 * 24) > time()) {
            return true;
        }
        return false;
    }

    public function canEdit() {
        global $ID;
        return (auth_quickaclcheck($ID) >= AUTH_EDIT);
    }

}
