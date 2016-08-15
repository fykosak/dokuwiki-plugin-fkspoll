<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Jan Prachař
 * @author Michal Červeňák <miso@fykos.cz>
 */
class helper_plugin_fkspoll extends DokuWiki_Plugin {

    const db_table_answer = "poll_answer";
    const db_table_question = "poll_question";
    const db_table_response = "poll_response";

    /**
     *
     * @var type 
     */
    public $FKS_helper;

    /**
     *
     * @var type 
     */
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

    /**
     * 
     * @param type $question_id
     * @return type
     */
    public function hasVoted($question_id) {
        return isset($_COOKIE['fkspoll-'.$question_id]) && $_COOKIE['fkspoll-'.$question_id];
    }

    /**
     * 
     * @param type $question_id
     * @param type $id
     */
    public function saveResponse($question_id,$id) {
        $sql = 'INSERT INTO '.self::db_table_response.' 
            (question_id,answer_id,users_id,remote_addr,remote_host,user_agent,accept,accept_language,referer,`from`,cookie,inserted) 
            VALUES(?,?,?,?,?,?,?,?,?,?,?,?)';
        $this->sqlite->query($sql,$question_id,$id,@$_SESSION['id'],@$_SERVER['REMOTE_ADDR'],@$_SERVER['REMOTE_HOST'],@$_SERVER['HTTP_USER_AGENT'],@$_SERVER['HTTP_ACCEPT'],@$_SERVER['HTTP_ACCEPT_LANGUAGE'],@$_SERVER['HTTP_REFERER'],$_SERVER['HTTP_FROM'],serialize($_COOKIE),\time());
    }

    public function CreateAnswers($id,$answers) {
        foreach ($answers as $answer) {
            $this->createAnswer($id,$answer);
        }
    }

    public function EditAnswers($question_id,$answers) {
        foreach ($answers as $id => $answer) {
            $this->EditAnswer($question_id,$id,$answer);
        }
    }

    public function EditAnswer($question_id,$id,$answer) {
        if(trim($answer)){
            $sql2 = 'UPDATE '.self::db_table_answer.' 
                    SET answer=?
                    WHERE answer_id=? ';
            $this->sqlite->query($sql2,$answer,$id);
        }else{
            $sql2 = 'DELETE FROM '.self::db_table_answer.'                     
                    WHERE answer_id=? ';
            $this->sqlite->query($sql2,$id);
        }
    }

    /**
     * 
     * @param type $question_id
     * @param type $text
     * @return type
     */
    public function createAnswer($question_id,$text) {
        if(!$this->Answer2ID($question_id,$text)){
            $sql2 = 'INSERT INTO '.self::db_table_answer.' 
                    (question_id,answer) 
                    VALUES(?,?)';
            $this->sqlite->query($sql2,$question_id,$text);
        }
        return $this->Answer2ID($question_id,$text);
    }

    /**
     * 
     * @param type $question_id
     * @param type $text
     * @return type
     */
    public function answer2ID($question_id,$text) {
        $sql1 = 'SELECT answer_id FROM '.self::db_table_answer.' WHERE question_id=? AND answer=?';
        $res = $this->sqlite->query($sql1,$question_id,$text);
        return $this->sqlite->res2single($res);
    }

    /**
     * 
     * @param type $poll
     * @return type
     */
    public function getResponses($poll) {
        $sql = 'SELECT answer,answer_id
            FROM '.self::db_table_answer.'            
            WHERE question_id = ?';
        $res = $this->sqlite->query($sql,$poll['question_id']);
        $ans = $this->sqlite->res2arr($res);
        $response = array();
        $sum = 0;
        foreach ($ans as $an) {
            $sql = 'SELECT COUNT(*) AS count
            FROM '.self::db_table_response.'        
            WHERE answer_id=? AND question_id=?
            GROUP BY answer_id';
            $res = $this->sqlite->query($sql,$an['answer_id'],$poll['question_id']);
            $c = $this->sqlite->res2single($res);
            $sum+=$c;
            $response[] = array('count' => $c,'answer' => $an['answer']);
        }
        $r = array();
        foreach ($response as $k => $answer) {
            $r[$k]['answer'] = $answer['answer'];
            $r[$k]['abs'] = $answer['count'] ? $answer['count'] : 0;
            $r[$k]['rel'] = $sum > 0 ? $answer['count'] / $sum : 0;
            $r[$k]['per'] = round(100 * $r[$k]['rel']);
            $v = $r[$k]['abs'];
            if($v == 1){
                $l = $this->getLang('N-SG_vote');
            }elseif($v > 0 && $v < 5){
                $l = $this->getLang('N-PL_vote');
            }else{
                $l = $this->getLang('G-PL_vote');
            }
            $r[$k]['unit'] = $l;
        }

        return $r;
    }

    /**
     * 
     * @param type $lang
     * @return boolean
     */
    public function getCurrentPolls($lang = 'cs') {
        $allPolls = $this->AllPolls($lang);
        $polls = array();
        foreach ($allPolls as $poll) {
            if($this->IsActualQuestion($poll['question_id'])){

                $poll['answers'] = $this->GetAnswers($poll['question_id']);
                $polls[] = $poll;
            }
        }
        return $polls;
    }

    public function getPollByID($id) {
        $sql = 'SELECT * FROM '.self::db_table_question.' WHERE question_id = ?';
        $res = $this->sqlite->query($sql,$id);
        foreach ($this->sqlite->res2arr($res) as $poll) {
            $poll['answers'] = $this->GetAnswers($poll['question_id']);
            return $poll;
        }
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public function getAnswers($id) {
        $sql = 'SELECT * FROM '.self::db_table_answer.'
            WHERE question_id=?';
        $res = $this->sqlite->query($sql,$id);
        $answers = $this->sqlite->res2arr($res);
        return $answers;
    }

    /**
     * 
     * @param type $poll
     * @return type
     */
    public function renderPoll($poll) {
        if($this->hasVoted($poll['question_id'])){
            return $this->getClosedPollHtml($poll);
        }else{
            return $this->getOpenPollHtml($poll);
        }
    }

    /**
     * 
     * @param array $poll
     * @return string
     */
    public function getClosedPollHtml($poll) {
        $poll['responses'] = $this->getResponses($poll);
        $max = max(array_map(function($d) {
                    return $d['per'];
                },$poll['responses']));


        $r .= $this->getPollHeadline($poll);
        $r .= $this->getPollDuration($poll);
        foreach ($poll['responses'] as $response) {
            $r .= '
<div class="answer closed">
    <div class="name">
        <span class="answer-text">'.htmlspecialchars($response['answer']).'</span>
        <span class="count-text" >'.$response['abs'].' '.$response['unit'].' ('.$response['per'].'%)'.'</span>
    </div>
    <div class="bar-container" >
        <span class="bar" data-percent="'.$response['per'] * 100 / ($max ? $max : 1).'"></span>
    </div>
</div>';
        }


        return $r;
    }

    private function getPollDuration($poll) {

        $d1 = date($this->getLang('date_format'),strtotime($poll['valid_from']));
        $d2 = date($this->getLang('date_format'),strtotime($poll['valid_to']));
        $date = $d1.' - '.$d2;
        return '<div class="duration">'.$date.'</div>';
    }

    private function getPollHeadline($poll) {
        return '<div class="question">
            <span class="question-text">'.htmlspecialchars($poll['question']).'</span>
                </div>';
    }

    /**
     * 
     * @param type $poll
     * @return string
     */
    private function getOpenPollHtml($poll) {
        //$poll['answers'] = array();
        if(!$poll){
            return;
        }

        $r .= $this->getPollHeadline($poll);
        $f = new dokuwiki\Form\Form();
        $f->setHiddenField('do','fkspoll_vote');
        $f->setHiddenField('question_id',$poll['question_id']);
        if($poll['type'] == 1){
            $this->addSinglePollFields($f,$poll);
        }elseif($poll['type'] == 2){
            $this->addMultiPollFields($f,$poll);
        }

        if($poll['new_answer'] == 1){
            $this->addNewAnswerField($f,$poll);
        }

        $f->addButton('submit',$this->getLang('send_answer'));
        $r .= $f->toHTML();
        return $r;
    }

    private function addNewAnswerField(dokuwiki\Form\Form &$form,$poll) {
        if($poll['type'] == 1){
            
        }
        $form->addTextInput('answer[text][]',null)->attr('placeholder',$this->getLang('another_answer'));
    }

    private function addSinglePollFields(dokuwiki\Form\Form &$form,$poll) {
        foreach ($poll['answers'] as $answer) {
            $form->addRadioButton('answer[id][]',$answer['answer'])->attr('value',$answer['answer_id']);
        }
    }

    private function addMultiPollFields(dokuwiki\Form\Form &$form,$poll) {
        foreach ($poll['answers'] as $answer) {
            $form->addCheckbox('answer[id][]',$answer['answer'])->attr('value',$answer['answer_id']);
        }
    }

    /**
     * 
     * @param array $p
     * @return int
     */
    public function createQuestion($p) {
        $lang = $p['lang'];
        $to = $p['valid_to'];
        $from = $p['valid_from'];
        $new_answer = $p['new_answer'];
        $type = $p['type'];
        $sectok = $p['sectok'];
        $q = $p['question'];
        $sql = 'INSERT INTO '.self::db_table_question.' 
                (question,lang,valid_from,valid_to,type,new_answer,sectok) 
                VALUES(?,?,?,?,?,?,?)';
        $this->sqlite->query($sql,$q,$lang,$from,$to,$type,$new_answer,$sectok);
        $sql2 = 'SELECT max(question_id) FROM '.self::db_table_question;
        $res = $this->sqlite->query($sql2);
        return $this->sqlite->res2single($res);
    }

    /**
     * 
     * @param array $p
     * @return int
     */
    public function editQuestion($p) {

        $to = $p['valid_to'];
        $from = $p['valid_from'];
        $new_answer = $p['new_answer'];
        $type = $p['type'];
        $sectok = $p['sectok'];
        $q = $p['question'];
        $sql = 'UPDATE '.self::db_table_question.' 
                SET question=?,valid_from=?,valid_to=?,type=?,new_answer=?,sectok=?            
                WHERE question_id = ?';

        $this->sqlite->query($sql,$q,$from,$to,$type,$new_answer,$sectok,$p['question_id']);
        $sql2 = 'SELECT max(question_id) FROM '.self::db_table_question;
        $res = $this->sqlite->query($sql2);
        return $this->sqlite->res2single($res);
    }

    public function allPolls($lang = 'cs') {
        $sql = 'SELECT * FROM '.self::db_table_question.' WHERE lang = ?';
        $res = $this->sqlite->query($sql,$lang);
        return $this->sqlite->res2arr($res);
    }

    public function isNewQuestion($sectok) {
        $sql = 'SELECT * FROM '.self::db_table_question.' WHERE sectok=? ';
        $res = $this->sqlite->query($sql,$sectok);
        $ar = $this->sqlite->res2arr($res);
        return (empty($ar) ? 1 : 0);
    }

    public function isActualQuestion($id) {
        $sql = 'SELECT * FROM '.self::db_table_question.' WHERE question_id=? ';
        $res = $this->sqlite->query($sql,$id);
        $ars = $this->sqlite->res2arr($res);

        foreach ($ars as $ar) {
            if(strtotime($ar['valid_from']) < time() && strtotime($ar['valid_to']) > time()){
                return true;
            }
        }
        return false;
    }

    public function canEdit() {
        global $ID;
        return (auth_quickaclcheck($ID) >= AUTH_EDIT);
    }

}
