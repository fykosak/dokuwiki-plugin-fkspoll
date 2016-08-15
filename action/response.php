<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Jan Prachař
 * @author Michal Červeňák <miso@fykos.cz>
 */
if(!defined('DOKU_INC')){
    die();
}

class action_plugin_fkspoll_response extends DokuWiki_Action_Plugin {

    private $helper;

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function __construct() {
        $this->helper = $this->loadHelper('fkspoll');
    }

    /**
     * 
     * @param Doku_Event_Handler $controller
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS','BEFORE',$this,'Response');
    }

    /**
     * 
     * @global type $TEXT
     * @global type $INPUT
     * @global type $ID
     * @param Doku_Event $event
     * @param type $param
     * @return void
     */
    public function Response(Doku_Event &$event) {
        global $INPUT;
        $act = $event->data;
        if($act !== 'fkspoll_vote'){
            return;
        }
        $event->preventDefault();
        $event->stopPropagation();


        $question_id = (int) $INPUT->str('question_id');
        if(!$this->helper->isActualQuestion($question_id)){
            msg('neplatná anketa');
            return;
        }
        if(!$this->helper->hasVoted($question_id)){
            $answers = $INPUT->param('answer');
            if(($INPUT->int('type') == 1) && ($answers['id'][0] != 0)){
                unset($answers['text']);
            }
            if(isset($answers['id'])){
                foreach ($answers['id'] as $id) {
                    $this->helper->saveResponse($question_id,$id);
                }
            }
            if(isset($answers['text'])){
                foreach ($answers['text'] as $text) {
                    $text = trim($text);
                    if($text == ""){
                        continue;
                    }
                    $id = $this->helper->createAnswer($question_id,$text);
                    $this->helper->saveResponse($question_id,$id);
                }
            }
            setcookie('fkspoll-'.$question_id,1,time() + 60 * 60 * 24 * 100);
            $_COOKIE['fkspoll-'.$question_id] = 1;

            header('Location: '.$_SERVER['REQUEST_URI']);
            exit();
        }else{
            msg($this->getLang('already_voted'),-1);
            return;
        }
        $event->data = 'show';
    }

}