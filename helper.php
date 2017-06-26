<?php

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

    // ****** creating answers ***** //
    public function createAnswers($id, $answers) {
        foreach ($answers as $answer) {
            $this->createAnswer($id, $answer);
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

    private function answer2ID($question_id, $text) {
        $res = $this->sqlite->query('SELECT answer_id FROM ' . self::db_table_answer . ' WHERE question_id=? AND answer=?', $question_id, $text);
        return $this->sqlite->res2single($res);
    }

    // *********** get poll **************//
    public function getPollByID($id) {
        $res = $this->sqlite->query('SELECT * FROM ' . self::db_table_question . ' WHERE question_id = ?', $id);
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

    // ************** helper units for poll  *********** //
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

    public function hasVoted($question_id) {
        return isset($_COOKIE['poll-' . $question_id]) && $_COOKIE['poll-' . $question_id];
    }

}
