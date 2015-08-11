<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michal Červeňák <miso@fykos.cz> 
 */
// must be run within Dokuwiki
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
        $question = $INPUT->str('question');
        if($question == ""){
            msg('empty question',-1);
            return;
        }


        
        $valid_to = $INPUT->str('valid_to_date').'T'.$INPUT->str('valid_to_time').':00';
        $valid_from = $INPUT->str('valid_from_date').'T'.$INPUT->str('valid_from_time').':00';
       

        $new_answer = $INPUT->int('new_answer') ? 1 : 0;
        $type = ( $INPUT->str('type') == 'multiple') ? 2 : 1;
        $id = $this->helper->CreateNewquestion($question,$valid_from,$valid_to,$type,$new_answer);

        $answers = $INPUT->param('answers');
        foreach ($answers as $answer) {
            $text = trim($answer);
            if($text == ""){
                continue;
            }

            $this->helper->CreateAnswer($id,$text);
        }
        msg('poll question has been created',1);
    }

    public function html() {

        global $lang;
        ptln('<h1>Create poll</h1>');

        $form = new Doku_Form(array('method' => 'POST'));
        $form->addHidden('fks_poll','create_poll');
        $form->addElement(form_makeTextField('question',"",$this->getLang('question')));
        $form->addElement('<br/>');
        $form->addElement(form_makeField('date','valid_from_date','1970-01-01',$this->getLang('valid_from_date'),''));
        $form->addElement(form_makeField('time','valid_from_time','00:00',$this->getLang('valid_from_time'),''));
        $form->addElement('<br/>');
        $form->addElement(form_makeField('date','valid_to_date','1970-01-01',$this->getLang('valid_to_date')));
        $form->addElement(form_makeField('time','valid_to_time','23:59',$this->getLang('valid_to_time')));
        $form->addElement('<br/>');

        $form->addElement(form_makeRadioField('type','single','single'));
        $form->addElement(form_makeRadioField('type','multiple','multiple'));

        $form->addElement(form_makeCheckboxField('new_answer',1,'answers_create'));

        $form->startFieldset($this->getLang('answers'));
        $form->addElement(form_makeTextField('answers[]',"",$this->getLang('answer')));
        $form->endFieldset();
        $form->addElement(form_makeButton('button',null,$this->getLang('add_answer'),array('id' => 'add_poll_answer')));
        $form->addElement(form_makeButton('submit',null,$lang['btn_save']));

        html_form('add',$form);
        echo '<hr>';
    }

}
