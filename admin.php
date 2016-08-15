<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Jan Prachař
 * @author Michal Červeňák <miso@fykos.cz>
 */
if(!defined('DOKU_INC')){
    die();
}

if(!defined('DOKU_PLUGIN')){
    define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
}

require_once(DOKU_PLUGIN.'admin.php');

class admin_plugin_fkspoll extends DokuWiki_Admin_Plugin {

    private $helper;

    public function __construct() {
        $this->helper = $this->loadHelper('fkspoll');
    }

    public function getMenuSort() {
        return 291;
    }

    public function forAdminOnly() {
        return false;
    }

    public function getMenuText() {
        $menutext = 'FKS_poll: POLL --'.$this->getLang('poll_menu');
        return $menutext;
    }

    public function handle() {
        global $INPUT;
        if($INPUT->str('fks_poll') !== 'create_poll'){
            return;
        }
        if($INPUT->str('msg') == 'ok'){
            msg('poll question has been created',1);
            return;
        }
        $data = array();
        if($this->IsValidNewQuestion($data)){
            $sectok = md5($data['question'].serialize($data));
            $data['sectok'] = $sectok;
            if($this->helper->IsNewQuestion($sectok)){
                $id = $this->helper->CreateNewquestion($data);

                foreach ($data['answers'] as $answer) {
                    $this->helper->CreateAnswer($id,$answer);
                }
                header('Location: '.$_SERVER['REQUEST_URI'].'&fks_poll=create_poll&msg=ok');
                exit;
            }else{
                msg('alredy added',0);
            }
        }else{
            return;
        }




        /* only one save!!! */
    }

    public function html() {
      
    }

    private function IsValidNewQuestion(&$data) {
        global $INPUT;
        $data['question'] = trim($INPUT->str('question'));
        if($data['question'] == ""){
            msg($this->getLang('empty_question'),-1);
            return FALSE;
        }
        if($INPUT->str('time_type') == 'week'){
            $s = strtotime($INPUT->str('valid_week'));
            $data['valid_from'] = date('Y-m-d\TH:i:s',$s);
            $data['valid_to'] = date('Y-m-d\TH:i:s',$s + (7 * 24 * 60 * 60) - 1);
        }elseif($INPUT->str('time_type') == 'date'){
            $data['valid_to'] = $INPUT->str('valid_to');
            $data['valid_from'] = $INPUT->str('valid_from');
        }else{
            msg('no date type selected',-1);
            return FALSE;
        }


        $data['new_answer'] = $INPUT->int('new_answer') ? 1 : 0;
        $data['type'] = ( $INPUT->str('type') == 'multiple') ? 2 : 1;
        $data['lang'] = $INPUT->str('lang');
        $data['answers'] = $INPUT->param('answers');

        foreach ($data['answers'] as $k => $answer) {
            $text = trim($answer);

            if($text == ""){
                unset($data['answers'][$k]);
            }else{
                $data['answers'][$k] = $text;
            }
        }

        if(!(count($data['answers']) > 1 || $data['new_answer'])){

            msg('nedovolená kombinácia parametrov',-1);
            return FALSE;
        }
        return true;
    }

}
