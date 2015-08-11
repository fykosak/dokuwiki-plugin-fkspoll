<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of helper
 *
 * @author miso
 */
class helper_plugin_fkspoll extends DokuWiki_Plugin {

    const db_table_answer = "poll_answer";
    const db_table_question = "poll_question";
    const db_table_response = "poll_response";

    public $FKS_helper;
    public $sqlite;

    public function __construct() {

        $this->FKS_helper = $this->loadHelper('fkshelper');


        $this->sqlite = $this->loadHelper('sqlite',false);
        $pluginName = $this->getPluginName();
        if(!$this->sqlite){
            msg($pluginName.': This plugin requires the sqlite plugin. Please install it.');
            return;
        }
        if(!$this->sqlite->init('fkspoll',DOKU_PLUGIN.$pluginName.DIRECTORY_SEPARATOR.'db'.DIRECTORY_SEPARATOR)){

            msg($pluginName.': Cannot initialize database.');
            return;
        }
    }

    public function hasVoted($question_id) {
        return isset($_COOKIE['fkspoll-'.$question_id]) && $_COOKIE['fkspoll-'.$question_id];
    }

    public function SaveResponse($question_id,$id) {
        $sql = 'INSERT INTO '.self::db_table_response.' (question_id,answer_id,users_id,remote_addr,remote_host,user_agent,accept,accept_language,referer,`from`,cookie,inserted) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
        ';
        $this->sqlite->query($sql,$question_id,$id,@$_SESSION['id'],$_SERVER['REMOTE_ADDR'],@$_SERVER['REMOTE_HOST'],@$_SERVER['HTTP_USER_AGENT'],@$_SERVER['HTTP_ACCEPT'],@$_SERVER['HTTP_ACCEPT_LANGUAGE'],@$_SERVER['HTTP_REFERER'],@$_SERVER['HTTP_FROM'],serialize($_COOKIE),time());
    }

    public function CreateAnswer($question_id,$text) {

        if(!$this->Answer2ID($question_id,$text)){
            $sql2 = 'INSERT INTO '.self::db_table_answer.' (question_id,answer) VALUES(?,?)';
            $this->sqlite->query($sql2,$question_id,$text);
        }
        return $this->Answer2ID($question_id,$text);
    }

    public function Answer2ID($question_id,$text) {

        $sql1 = 'SELECT answer_id FROM '.self::db_table_answer.' WHERE question_id=? AND answer=?';
        $res = $this->sqlite->query($sql1,$question_id,$text);
        return $this->sqlite->res2single($res);
    }

    public function getResponses($poll) {
        $sql = 'SELECT answer,answer_id
            FROM poll_answer            
            WHERE question_id = ?';

        $res = $this->sqlite->query($sql,$poll['question_id']);
        $ans = $this->sqlite->res2arr($res);

        $response = array();
        $sum = 0;
        foreach ($ans as $an) {
            $sql = 'SELECT COUNT(*) AS count
            FROM '.self::db_table_response.'        
            WHERE answer_id=?
            GROUP BY answer_id';
            $res = $this->sqlite->query($sql,$an['answer_id']);
            $c = $this->sqlite->res2single($res);
            $sum+=$c;
            $response[] = array('count' => $c,'answer' => $an['answer']);
        }


        $responses = array();
        foreach ($response as $k => $answer) {
            $responses[$k]['answer'] = $answer['answer'];
            $responses[$k]['abs'] = $answer['count'] ? $answer['count'] : 0;
            $responses[$k]['rel'] = $sum > 0 ? $answer['count'] / $sum : 0;
            $responses[$k]['per'] = round(100 * $responses[$k]['rel']);
            $v = $responses[$k]['abs'];
            if($v == 1){
                $l = $this->getLang('N-SG_vote');
            }elseif($v > 0 && $v < 5){
                $l = $this->getLang('G-SG_vote');
            }else{
                $l = $this->getLang('N-PL_vote');
            }

            $responses[$k]['absCountUnit'] = $l;
        }

        return $responses;
    }

    public function GetCurrentPoll() {
        $polls = $this->AllPolls();
        foreach ($polls as $poll) {
            $now = time();
            /* because fuck SQL STRTOTIME() don't know!!! */
            if((strtotime($poll['valid_from']) < $now) && (strtotime($poll['valid_to']) > $now)){
                $poll['answers'] = $this->GetAnswers($poll['question_id']);
                return $poll;
            }
        }
        return FALSE;
    }

    public function GetAnswers($id) {
        $sql = 'SELECT * FROM '.self::db_table_answer.'
            WHERE question_id=?';

        $res = $this->sqlite->query($sql,$id);
        $answers = $this->sqlite->res2arr($res);

        return $answers;
    }

    public function getHtml($poll) {
        if($this->hasVoted($poll['question_id'])){
            return $this->getClosedPollHtml($poll);
        }else{
            return $this->getOpenPollHtml($poll);
        }
    }

    public function getClosedPollHtml($poll,$archive = FALSE) {
        $poll['responses'] = $this->getResponses($poll);


        $d1 = date('d\.m\. Y H:i:s',strtotime($poll['valid_from']));
        $d2 = date('d\.m\. Y H:i:s',strtotime($poll['valid_to']));
        $title = $d1.' &ndash; '.$d2;

        $r = '
        <div class="FKS_poll closed">';
        $r .= '
            <h3>'.$poll['question'].'</h3>';
        $r .= '
            <label>'.$title.'</label>';
        foreach ($poll['responses'] as $response) {

            $r .= '<div class="answer">';
            $r .= '<div class="bar" style="width: '.$response['per'].'%">';
            $r .= '</div>';
            $r .= '<div class="name">';
            $r .= '<span class="text">'.htmlspecialchars($response['answer']).'</span>';
            $r .= '<span class="count">'.$response['abs'].' ('.$response['per'].'%)'.'</span>';

            $r .= '</div>';
            $r .= '</div>';
        }
        $r .= '</div>';


        return $r;
    }

    public function getOpenPollHtml($poll) {
        //$poll['answers'] = array();
        if(!$poll){
            return;
        }
        $r.='
        <div class="FKS_poll open">';

        $r .= '
            <h3>'.$poll['question'].'</h3>';
        $form = new Doku_Form(array('method' => 'POST'));
        $form->addHidden('target','fkspoll');
        $form->addHidden('question_id',$poll['question_id']);

        if($poll['type'] == 2){
            /* single */
            foreach ($poll['answers'] as $answer) {
                $form->addElement('<div class="answer">');
                $form->addElement(form_makeRadioField('answer[id][]',$answer['answer_id'],$answer['answer']));

                $form->addElement('</div>');
            }
        }elseif($poll['type'] == 1){
            foreach ($poll['answers'] as $answer) {
                $form->addElement('<div class="answer">');
                $form->addElement(form_makeCheckboxField('answer[id][]',$answer['answer_id'],$answer['answer']));


                $form->addElement('</div>');
            }

            /* multi */
        }
        ob_start();
        if($poll['new_answer'] == "1"){
            $form->addElement(form_makeTextField('answer[text][]',"",'iná odpoveď'));
        }
        $form->addElement(form_makeButton('submit',null,$this->getLang('send_answer')));
        html_form('poll',$form);
        $r.=ob_get_contents();
        ob_end_clean();
        $r.='</div>';

        return $r;
    }

    public function CreateNewQuestion($q,$from,$to,$type = 1,$new_answer = 0) {

        $sql = 'INSERT INTO '.self::db_table_question.' 
                (question,valid_from,valid_to,type,new_answer) 
                VALUES(?,?,?,?,?)';
        $this->sqlite->query($sql,$q,$from,$to,$type,$new_answer);

        $sql2 = 'SELECT max(question_id) FROM '.self::db_table_question;

        $res = $this->sqlite->query($sql2);
        return $this->sqlite->res2single($res);
    }

    public function AllPolls() {
        $sql = 'SELECT * FROM '.self::db_table_question;
        $res = $this->sqlite->query($sql);
        return $this->sqlite->res2arr($res);
    }

}
