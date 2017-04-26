<?php

use dokuwiki\Form\Form;

class action_plugin_fkspoll_edit extends DokuWiki_Action_Plugin {
    /**
     * @var helper_plugin_fkspoll
     */
    private $helper;

    public function __construct() {
        $this->helper = $this->loadHelper('fkspoll');
    }

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'editPoll');
        $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'tplEditPoll');
    }

    public function tplEditPoll(Doku_Event &$event) {
        global $INPUT;
        if (!$this->helper->canEdit()) {
            return;
        }
        if ($event->data !== helper_plugin_fkspoll::TARGET) {
            return;
        }

        if ($INPUT->param('poll')['do'] !== 'edit') {
            return;
        }
        $event->preventDefault();
        $questionID = $INPUT->str('question_id');

        ptln('<h1>' . hsc($questionID ? $this->getLang('edit_poll') : $this->getLang('create_poll')) . '</h1>');
        echo $this->createCreateForm($questionID);
    }

    private function createCreateForm($questionID) {
        global $lang;
        $poll = [];

        $form = new Form();
        $form->attr('class', 'poll_edit');
        $form->setHiddenField('poll[do]', 'save');
        $form->setHiddenField('target', helper_plugin_fkspoll::TARGET);

        if ($questionID) {
            $poll = $this->helper->getPollByID($questionID);
            $form->setHiddenField('question_id', $questionID);
        }
        $this->addQuestionFields($form, $poll, $questionID);
        $this->addValidFields($form, $poll);
        $this->addOthersParamsFields($form, $poll, $questionID);
        $this->addAnswersFields($form, $poll, $questionID);

        $form->addButton('submit', $lang['btn_save'])
            ->attr('type', 'submit')
            ->addClass('btn btn-success');
        return $form->toHTML();
    }

    private function addOthersParamsFields(Form &$form, $poll, $questionID) {
        $form->addFieldsetOpen($this->getLang('poll_param'))
            ->attr('class', 'params');
        $form->addTagOpen('div')
            ->addClass('form-check');
        $single = $form->addRadioButton('answer-type', $this->getLang('single'))
            ->val('single')
            ->attrs(['required' => 'required']);
        $form->addTagClose('div');

        $form->addTagOpen('div')
            ->addClass('form-check');
        $multiple = $form->addRadioButton('answer-type', $this->getLang('multiple'))
            ->val('multiple')
            ->attrs(['required' => 'required']);
        $form->addTagClose('div');

        $new_answer = $form->addCheckbox('new_answer', $this->getLang('new_answer'));
        //    $form->addDropdown('lang',array(),$lang['i_chooselang'])->options(array('cs','en'));
        $form->addFieldsetClose();
        if ($questionID) {
            ($poll['type'] == 1) ? $single->attr('checked', 'checked') : $multiple->attr('checked', 'checked');

            $new_answer->attr($poll['new_answer'] ? 'checked' : '', true);
        }
    }

    private function addAnswersFields(Form &$form, $poll, $questionID) {
        $form->addFieldsetOpen($this->getLang('answers'))
            ->attr('class', 'answers');
        if ($questionID) {
            foreach ($poll['answers'] as $answer) {
                $form->addTagOpen('div')
                    ->addClass('form-group');
                $form->addTextInput('answers[' . $answer['answer_id'] . ']', null)
                    ->addClass('form-control')
                    ->attrs(['placeholder' => $this->getLang('answer')])
                    ->val($answer['answer']);
                $form->addTagClose('div');
            }
        }

        $form->addTagOpen('div')
            ->addClass('form-group new-answer');
        $form->addTextInput('new_answers[]', null)
            ->addClass('form-control')
            ->attrs(['placeholder' => $this->getLang('answer')]);
        $form->addTagClose('div');

        $form->addButton('button', $this->getLang('add_answer'))
            ->attr('id', 'add-answer')
            ->addClass('btn btn-primary')
            ->attr('type', 'button');
        $form->addFieldsetClose();
    }

    private function addQuestionFields(Form &$form, $poll, $questionID) {
        $form->addFieldsetOpen($this->getLang('question'))
            ->attr('class', 'question');
        $form->addTagOpen('div')
            ->addClass('form-group');
        $question = $form->addTextInput('question')
            ->attrs([
                'placeholder' => $this->getLang('question'),
                'required' => 'required',
                'pattern' => "\S.*"
            ])
            ->addClass('form-control');
        $form->addTagClose('div');
        $form->addFieldsetClose();
        if ($questionID) {
            $question->val($poll['question']);
        }
    }

    private function addValidFields(Form &$form, $poll) {
        $form->addFieldsetOpen($this->getLang('choose_date-week'))
            ->attr('class', 'validity');
        $form->addTagOpen('div')
            ->addClass('form-group');

        $validFromElement = new dokuwiki\Form\InputElement('datetime-local', 'valid-from', null);
        $validFromElement->addClass('form-control')
            ->val($poll['question_id'] ? date('Y-m-d\TH:i:s', strtotime($poll['valid_from'])) : date('Y-m-d\TH:i:s', time()));
        $form->addElement($validFromElement);
        $form->addTagClose('div');
        $form->addTagOpen('div')
            ->addClass('form-group');
        $validToElement = new dokuwiki\Form\InputElement('datetime-local', 'valid-to', null);
        $validToElement->addClass('form-control')
            ->val($poll['question_id'] ? date('Y-m-d\TH:i:s', strtotime($poll['valid_to'])) : date('Y-m-d\TH:i:s', time()));
        $form->addElement($validToElement);
        $form->addTagClose('div');
        $form->addFieldsetClose();
    }

    public function editPoll(Doku_Event &$event) {
        global $INPUT;
        if (!$this->helper->canEdit()) {
            return;
        }
        if ($event->data !== helper_plugin_fkspoll::TARGET) {
            return;
        }
        $event->preventDefault();
        $event->stopPropagation();
        switch ($INPUT->param('poll')['do']) {
            case 'save':
                $this->savePoll();
                $event->data = 'show';
                break;
            case 'edit':
                break;
            default :
                return;
        }
    }

    private function savePoll() {
        global $INPUT;

        if ($INPUT->str('msg') == 'ok') {
            msg('poll question has been created', 1);
            return;
        }
        $data = [];
        if ($this->isValid($data)) {

            $sectok = md5($data['question'] . serialize($data));
            $data['sectok'] = $sectok;
            if ($data['question_id']) {
                $this->helper->editQuestion($data);
                $this->helper->editAnswers($data['question_id'], $data['answers']);
                $this->helper->createAnswers($data['question_id'], $data['new_answers']);
                msg('edited');
            } else {
                if ($this->helper->isNewQuestion($sectok)) {
                    $id = $this->helper->createQuestion($data);
                    $this->helper->createAnswers($id, $data['new_answers']);
                    msg('added');
                } else {
                    msg('alredy added', 0);
                }
            }
        } else {
            return;
        }
    }

    private function isValid(&$data) {
        global $INPUT;
        $data['question_id'] = $INPUT->str('question_id');
        $data['question'] = trim($INPUT->str('question'));
        if ($data['question'] == "") {
            msg($this->getLang('empty_question'), -1);
            return FALSE;
        }
        $data['valid_to'] = $INPUT->str('valid-to');
        $data['valid_from'] = $INPUT->str('valid-from');

        $data['new_answer'] = $INPUT->int('new_answer') ? 1 : 0;
        $data['type'] = ($INPUT->str('answer-type') == 'multiple') ? 2 : 1;
        $data['lang'] = 'cs';

        $data['answers'] = $INPUT->param('answers', []);
        $data['new_answers'] = [];
        foreach ($INPUT->param('new_answers') as $k => $answer) {
            $text = trim($answer);

            if ($text == "") {
                unset($data['new_answers'][$k]);
            } else {
                $data['new_answers'][$k] = $text;
            }
        }
        return true;
    }
}
