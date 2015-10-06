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
                $id = $this->helper->CreateNewquestion($data['question'],$data);
                var_dump($data);
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

        global $lang;
        helper_plugin_fkshelper::HTML5Test();
        ptln('<h1>'.$this->getLang('create_poll').'</h1>');

        $form = new Doku_Form(array('method' => 'POST'));
        $form->addHidden('fks_poll','create_poll');
        $form->startFieldset($this->getLang('question'));
        $form->addElement(form_makeTextField('question',"",$this->getLang('question'),null,'block',array('placeholder' => $this->getLang('question'),'required' => 'required','pattern' => "\S.*")));
        $form->endFieldset();


        $form->startFieldset($this->getLang('choose_date-week'));
        $form->addElement(form_makeRadioField('time_type','week',$this->getLang('choose_week'),null,null,array('required' => 'required')));
        $form->addElement(form2_makeWeekField('valid_week',null,$this->getLang('valid_week'),null,'block'));
        $form->addElement('<hr />');
        $form->addElement(form_makeRadioField('time_type','date',$this->getLang('choose_date'),null,null,array('required' => 'required')));
        $form->addElement(form2_makeDateTimeField('valid_from',date('Y-m-d',time()).'T00:00:00',$this->getLang('valid_from'),null,'block'));
        $form->addElement(form2_makeDateTimeField('valid_to',date('Y-m-d',time() + (7 * 24 * 60 * 60)).'T23:59:59',$this->getLang('valid_to'),null,'block'));
        $form->endFieldset();

        $form->startFieldset($this->getLang('poll_param'));
        $form->addElement(form_makeRadioField('type','single',$this->getLang('single'),null,null,array('required' => 'required')));
        $form->addElement(form_makeRadioField('type','multiple',$this->getLang('multiple'),null,null,array('required' => 'required')));
        $form->addElement('<br/>');
        $form->addElement(form_makeCheckboxField('new_answer',1,$this->getLang('new_answer')));
        $form->addElement('<br/>');
        $form->addElement(form_makeListboxField('lang',array('cs','en'),'',$lang['i_chooselang']));
        $form->endFieldset();


        $form->startFieldset($this->getLang('answers'));
        $form->addElement(form_makeButton('button',null,$this->getLang('add_answer'),array('id' => 'add_poll_answer')));
        $form->addElement(form_makeTextField('answers[]',"",$this->getLang('answer'),null,'block',array('placeholder' => $this->getLang('answer'))));
        $form->endFieldset();

        $form->addElement(form_makeButton('submit',null,$lang['btn_save']));

        html_form('add',$form);
        echo '<hr>';
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

        if(!(count($data['answers'])>1|| $data['new_answer'])){

            msg('nedovolená kombinácia parametrov',-1);
            return FALSE;
        }
        return true;
    }

}
