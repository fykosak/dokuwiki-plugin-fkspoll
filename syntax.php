<?php

use dokuwiki\Form\Form;


class syntax_plugin_fkspoll extends DokuWiki_Syntax_Plugin {
    /**
     * @var helper_plugin_fkspoll
     */
    private $helper;

    public function __construct() {
        $this->helper = $this->loadHelper('fkspoll');
    }

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getAllowedTypes() {
        return [];
    }

    public function getSort() {
        return 23;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~FKSPOLL-?[a-zA-Z-]*?~~', $mode, 'plugin_fkspoll');
    }

    public function handle($match, $state) {
        global $conf;
        $matches = [];
        $lang = $conf['lang'];
        $type = false;
        if (preg_match('/~~FKSPOLL(-([A-Z]*?))?(-([a-z]*?))?~~/', $match, $matches)) {
            list(, , $type, , $lang) = $matches;
        }
        return array($state, ['lang' => $lang, 'type' => strtolower($type)]);
    }

    public function render($mode, Doku_Renderer &$renderer, $data) {
        list($state, $param) = $data;
        if ($mode == 'xhtml') {
            switch ($state) {
                case DOKU_LEXER_SPECIAL:
                    switch ($param['type']) {
                        case 'archive':
                            $this->renderArchive($renderer, $param);
                            break;
                        default:
                            $this->renderOpenPoll($renderer, $param);
                            break;
                    }
                    return false;
                default:
                    return true;
            }
        }
        return false;
    }

    private function renderArchive(Doku_Renderer &$renderer, $param) {


        $renderer->nocache();
        $polls = $this->allPolls($param['lang'] ?: 'cs');
        $renderer->doc .= '<div class="polls archive card-columns">';

        foreach (array_reverse($polls) as $poll) {
            $renderer->doc .= '<div class="card">';
            $renderer->doc .= $this->getClosedPollHtml($poll);
            $this->renderFields($renderer, $poll);
            $renderer->doc .= '</div>';
        }
        $renderer->doc .= '</div>';
    }

    private function renderOpenPoll(Doku_Renderer &$renderer, $param) {
        $renderer->nocache();
        $renderer->doc .= '<div>';
        $polls = $this->getCurrentPolls($param['lang'] ?: 'cs');
        foreach ($polls as $poll) {
            $renderer->doc .= '<div class="poll card mb-3">';
            $renderer->doc .= $this->renderPoll($poll);
            $this->renderFields($renderer, $poll);
            $renderer->doc .= '</div>';
        }
        $renderer->doc .= '</div>';
    }

    private function renderFields(Doku_Renderer &$renderer, $poll) {
        global $ID;
        $renderer->doc .= '<div class="list-group list-group-flush">';
        $renderer->doc .= '<div class="list-group-item"><a href="' . wl($this->getConf('archive-path')) . '">' .
            $this->getLang('archive') . '</a></div>';
        if ($this->helper->canEdit()) {
            $renderer->doc .= '<div class="list-group-item">
                <a href="' . wl($ID,
                    [
                        'do' => helper_plugin_fkspoll::TARGET,
                        'poll[do]' => 'edit',
                        'question_id' => $poll['question_id']
                    ]) . '">' . $this->getLang('edit_poll') . '
                </a>
                </div>';

            $renderer->doc .= '<div class="list-group-item"><a href="' . wl($ID,
                    [
                        'do' => helper_plugin_fkspoll::TARGET,
                        'poll[do]' => 'edit',
                    ]) . '">' . $this->getLang('create_poll') . '</a></div>';
        }
        $renderer->doc .= '</div>';
    }

    private function renderPoll($poll) {
        if ($this->helper->hasVoted($poll['question_id'])) {
            return $this->getClosedPollHtml($poll);
        } else {
            return $this->getOpenPollHtml($poll);
        }
    }

    private function getResponses($poll) {
        $res = $this->helper->sqlite->query('SELECT answer,answer_id
            FROM ' . helper_plugin_fkspoll::db_table_answer . '            
            WHERE question_id = ?',
            $poll['question_id']);
        $ans = $this->helper->sqlite->res2arr($res);
        $response = [];
        $sum = 0;
        foreach ($ans as $an) {
            $res = $this->helper->sqlite->query('SELECT COUNT(*) AS count
            FROM ' . helper_plugin_fkspoll::db_table_response . '        
            WHERE answer_id=? AND question_id=?
            GROUP BY answer_id',
                $an['answer_id'],
                $poll['question_id']);
            $c = $this->helper->sqlite->res2single($res);
            $sum += $c;
            $response[] = ['count' => $c, 'answer' => $an['answer']];
        }
        $r = [];
        foreach ($response as $k => $answer) {
            $r[$k]['answer'] = $answer['answer'];
            $r[$k]['abs'] = $answer['count'] ? $answer['count'] : 0;
            $r[$k]['rel'] = $sum > 0 ? $answer['count'] / $sum : 0;
            $r[$k]['per'] = round(100 * $r[$k]['rel']);
            $v = $r[$k]['abs'];
            if ($v == 1) {
                $l = $this->getLang('N-SG_vote');
            } elseif ($v > 0 && $v < 5) {
                $l = $this->getLang('N-PL_vote');
            } else {
                $l = $this->getLang('G-PL_vote');
            }
            $r[$k]['unit'] = $l;
        }

        return $r;
    }

    private function getClosedPollHtml($poll) {
        $html = '';
        $poll['responses'] = $this->getResponses($poll);
        $max = max(array_map(function ($d) {
            return $d['per'];
        },
            $poll['responses']));


        $html .= $this->getPollHeadline($poll);
        $html .= '<div class="card-block">';
        $html .= $this->getPollDuration($poll);
        foreach ($poll['responses'] as $key => $response) {
            $html .= '<p>';
            // $html .= '<div>';
            $html .= '<span>' . htmlspecialchars($response['answer']) . '</span>';
            $html .= ' <small class="text-muted">' . $response['abs'] . ' ' . $response['unit'] . ' (' .
                $response['per'] . '%)' . '</small>';
            // $html .= '</div>';
            $percent = (float)($response['per'] * 100 / ($max ? $max : 1));

            $html .= '<div class="progress">';
            $html .= '<div class="progress-bar" role="progressbar" style="width: ' . $percent .
                '% " aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . number_format($percent, 2, ',', '') .
                '%</div>';
            $html .= '</div>';
            $html .= '</p>';
        }
        $html .= '</div>';
        return $html;
    }

    private function getOpenPollHtml($poll) {
        $html = '';
        if (!$poll) {
            return '';
        }

        $html .= $this->getPollHeadline($poll);
        $html .= '<div class="card-block">';
        $form = new Form(['action'=>'.']);
        $form->setHiddenField('target', helper_plugin_fkspoll::TARGET);
        $form->setHiddenField('poll[do]', 'vote');
        $form->setHiddenField('poll[question-id]', $poll['question_id']);
        $form->addTagOpen('div')->addClass('form-group');
        if ($poll['type'] == 1) {
            $this->addSinglePollFields($form, $poll);
        } elseif ($poll['type'] == 2) {
            $this->addMultiPollFields($form, $poll);
        }
        $form->addTagClose('div');

        if ($poll['new_answer'] == 1) {
            $form->addTagOpen('div')->addClass('form-group');
            $this->addNewAnswerField($form, $poll);
            $form->addTagClose('div');
        }

        $form->addButton('submit', $this->getLang('send_answer'))->addClass('btn btn-success');

        $html .= $form->toHTML();
        $html .= '</div>';
        return $html;
    }

    private function addNewAnswerField(Form &$form, $poll) {
        if ($poll['type'] == 1) {
        }
        $form->addTextInput('answer[text][]', null)->attr('placeholder', $this->getLang('another_answer'))
            ->addClass('form-control');
    }

    private function addSinglePollFields(Form &$form, $poll) {
        foreach ($poll['answers'] as $answer) {
            $form->addTagOpen('div')->addClass('form-check');
            $radio = new \fks\Form\CheckableElement('radio', 'answer[id][]', $answer['answer']);
            $radio->val($answer['answer_id']);
            $form->addElement($radio);
            $form->addTagClose('div');
        }
    }

    private function addMultiPollFields(Form &$form, $poll) {
        foreach ($poll['answers'] as $answer) {
            $form->addTagOpen('div')->addClass('form-check');
            $checkbox = new \fks\Form\CheckableElement('checkbox', 'answer[id][]', $answer['answer']);
            $checkbox->val($answer['answer_id']);
            $form->addElement($checkbox);;
            $form->addTagClose('div');
        }
    }

    private function getPollDuration($poll) {
        $dateFrom = date($this->getLang('date_format'), strtotime($poll['valid_from']));
        $dateTo = date($this->getLang('date_format'), strtotime($poll['valid_to']));
        return '<small class="card-subtitle text-muted">' . $dateFrom . ' - ' . $dateTo . '</small>';
    }

    private function getPollHeadline($poll) {
        return '<div class="card-header card-inverse">' . htmlspecialchars($poll['question']) . '</div>';
    }

    private function getCurrentPolls($lang = 'cs') {
        $allPolls = $this->allPolls($lang);
        $polls = [];
        foreach ($allPolls as $poll) {
            if ($this->helper->isActualQuestion($poll['question_id'])) {
                $poll['answers'] = $this->helper->getAnswers($poll['question_id']);
                $polls[] = $poll;
            }
        }
        return $polls;
    }

    private function allPolls($lang = 'cs') {
        $res = $this->helper->sqlite->query('SELECT * FROM ' . helper_plugin_fkspoll::db_table_question .
            ' WHERE lang = ?',
            $lang);
        return $this->helper->sqlite->res2arr($res);
    }
}
