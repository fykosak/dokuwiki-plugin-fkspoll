<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Michal Červeňák <miso@fykos.cz>
 */
if(!defined('DOKU_INC')){
    die();
}

class action_plugin_fkspoll_edit extends DokuWiki_Action_Plugin {

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
        $controller->register_hook('ACTION_ACT_PREPROCESS','BEFORE',$this,'EditPoll');
        $controller->register_hook('TPL_ACT_UNKNOWN','BEFORE',$this,'tplEditPoll');
    }

    public function tplEditPoll(Doku_Event &$event) {


        if($event->data != 'fkspoll_edit'){
            return;
        }

        $event->preventDefault();
        global $INPUT;
        global $lang;
        ptln('<h1>'.hsc($this->getLang('create_poll')).'</h1>');

        $poll = array();

        /**
         * 
         */
        $form = new dokuwiki\Form\Form();
        $form->attr('class','poll_edit');
        $form->setHiddenField('do','fkspoll_save');
        $question_id = $INPUT->str('question_id');

        if($question_id){
            $poll = $this->helper->getPollByID($question_id);

            $form->setHiddenField('question_id',$question_id);
        }

        $form->addFieldsetOpen($this->getLang('question'))
                ->attr('class','question');
        $question = $form->addTextInput('question')
                ->attrs(array('placeholder' => $this->getLang('question'),'required' => 'required','pattern' => "\S.*"));



        $form->addFieldsetClose();


        $form->addFieldsetOpen($this->getLang('choose_date-week'))
                ->attr('class','validity');


        $form->addTextInput('valid_from',$this->getLang('valid_from'))
                ->val($poll['question_id'] ? $poll['valid_from'] : date('Y-m-d',time()))
                ->attr('pattern','[0-9]{4}-[0-9]{2}-[0-9]{2}')
                ->attr('placeholder','YYYY-MM-DD');
        $form->addTextInput('valid_to',$this->getLang('valid_to'))
                ->val($poll['question_id'] ? $poll['valid_to'] : date('Y-m-d',time() + (7 * 24 * 60 * 60)))
                ->attr('pattern','[0-9]{4}-[0-9]{2}-[0-9]{2}')
                ->attr('placeholder','YYYY-MM-DD');


        $form->addFieldsetClose();



        $form->addFieldsetOpen($this->getLang('poll_param'))
                ->attr('class','params');


        $single = $form->addRadioButton('answer-type',$this->getLang('single'))
                ->val('single')
                ->attrs(array('required' => 'required'));
        $multiple = $form->addRadioButton('answer-type',$this->getLang('multiple'))
                ->val('multiple')
                ->attrs(array('required' => 'required'));



        $new_answer = $form->addCheckbox('new_answer',$this->getLang('new_answer'));
        //    $form->addDropdown('lang',array(),$lang['i_chooselang'])->options(array('cs','en'));
        $form->addFieldsetClose();


        $form->addFieldsetOpen($this->getLang('answers'))
                ->attr('class','answers');
        if($question_id){
            foreach ($poll['answers'] as $answer) {
                $form->addTextInput('answers['.$answer['answer_id'].']',$this->getLang('answers'))
                        ->attrs(array('placeholder' => $this->getLang('answer')))
                        ->val($answer['answer']);
            }
        }else{
            
        }
        $form->addTextInput('new_answers[]',$this->getLang('answers'))
                ->attrs(array('placeholder' => $this->getLang('answer')))
                ->getLabel()
                ->attr('class','new');
        $form->addButton('button',$this->getLang('add_answer'))
                ->attr('type','button')->attr('class','add_answer');

        $form->addFieldsetClose();

        $form->addButton('submit',$lang['btn_save'])
                ->attr('type','submit');

        if($question_id){
            $question->val($poll['question']);
            ($poll['type'] == 1) ? $single->attr('checked','checked') : $multiple->attr('checked','checked');

            $new_answer->attr($poll['new_answer'] ? 'checked' : '',true);
        }



        echo $form->toHTML();
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
    public function EditPoll(Doku_Event &$event) {

        $act = $event->data;

        if(!$this->helper->canEdit()){
            return;
        }

        switch ($act) {
            case 'fkspoll_save':
                $this->savePoll();
                $event->data = 'show';
                break;
            case 'fkspoll_edit':

                break;
            default : return;
        }

        $event->preventDefault();
        $event->stopPropagation();
    }

    private function savePoll() {
        global $INPUT;

        if($INPUT->str('msg') == 'ok'){
            msg('poll question has been created',1);
            return;
        }
        $data = array();
        if($this->IsValid($data)){

            $sectok = md5($data['question'].serialize($data));
            $data['sectok'] = $sectok;
            if($data['question_id']){
                $this->helper->EditQuestion($data);
                $this->helper->EditAnswers($data['question_id'],$data['answers']);
                $this->helper->CreateAnswers($data['question_id'],$data['new_answers']);
                msg('edited');
            }else{
                if($this->helper->IsNewQuestion($sectok)){
                    $id = $this->helper->CreateQuestion($data);
                    $this->helper->CreateAnswers($id,$data['new_answers']);
                    msg('added');
                }else{
                    msg('alredy added',0);
                }
            }
        }else{
            return;
        }
    }

    private function IsValid(&$data) {

        global $INPUT;
        $data['question_id'] = $INPUT->str('question_id');
        $data['question'] = trim($INPUT->str('question'));
        if($data['question'] == ""){
            msg($this->getLang('empty_question'),-1);
            return FALSE;
        }


        $data['valid_to'] = $INPUT->str('valid_to');
        $data['valid_from'] = $INPUT->str('valid_from');



        $data['new_answer'] = $INPUT->int('new_answer') ? 1 : 0;
        $data['type'] = ( $INPUT->str('answer-type') == 'multiple') ? 2 : 1;
        $data['lang'] = 'cs';

        $data['answers'] = $INPUT->param('answers',array());
        $data['new_answers'] = array();
        foreach ($INPUT->param('new_answers') as $k => $answer) {
            $text = trim($answer);

            if($text == ""){
                unset($data['new_answers'][$k]);
            }else{
                $data['new_answers'][$k] = $text;
            }
        }

        return true;
    }

}
