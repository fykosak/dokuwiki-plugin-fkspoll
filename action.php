<?php

/**
 * DokuWiki Plugin fksnewsfeed (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michal Červeňák <miso@fykos.cz>
 */
if(!defined('DOKU_INC')){
    die();
}

class action_plugin_fkspoll extends DokuWiki_Action_Plugin {

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
     * @return type
     */
    public function Response(Doku_Event &$event) {
      
        
       
        global $INPUT;
        if($INPUT->str('target') !== 'fkspoll'){
            return;
        }
        $answers = $INPUT->param('answer');
        $question_id = (int) $INPUT->str('question_id');

        if(!$this->helper->HasVoted($question_id)){
            if(isset($answers['id'])){
                foreach ($answers['id'] as $id) {
                    $this->helper->SaveResponse($question_id,$id);
                }
            }


            if(isset($answers['text'])){
                foreach ($answers['text'] as $text) {
                    $text = trim($text);
                    if($text == ""){
                        continue;
                    }
                    $id = $this->helper->CreateAnswer($question_id,$text);
                    $this->helper->SaveResponse($question_id,$id);
                }
            }


            setcookie('fkspoll-'.$question_id,1,time() + 60 * 60 * 24 * 100);
            $_COOKIE['fkspoll-'.$question_id] = 1;
        }else{
            msg('Alredy voted!!!',-1);
            return;
        }
    }

}