<?php

class syntax_plugin_fkspoll extends DokuWiki_Syntax_Plugin {
    /**
     * @var helper_plugin_fkspoll
     */
    private $helper;

    private static $types = ['danger', 'warning', 'info', 'primary', 'success'];

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
                            $this->renderPoll($renderer, $param);
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
        $polls = $this->helper->allPolls($param['lang'] ?: 'cs');
        $renderer->doc .= '<div class="polls archive card-columns">';

        foreach (array_reverse($polls) as $poll) {
            $type = self::$types[array_rand(self::$types)];
            $renderer->doc .= '<div class="card card-outline-' . $type . '">';

            $renderer->doc .= $this->helper->getClosedPollHtml($poll, $type);
            $this->renderFields($renderer, $poll);
            $renderer->doc .= '</div>';
        }
        $renderer->doc .= '</div>';
    }

    private function renderPoll(Doku_Renderer &$renderer, $param) {
        $renderer->nocache();
        $renderer->doc .= '<div>';
        $polls = $this->helper->getCurrentPolls($param['lang'] ?: 'cs');
        foreach ($polls as $poll) {
            $type = self::$types[array_rand(self::$types)];
            $renderer->doc .= '<div class="poll card card-outline-' . $type . ' mb-3">';
            $renderer->doc .= $this->helper->renderPoll($poll, $type);
            $this->renderFields($renderer, $poll);
            $renderer->doc .= '</div>';
        }
        $renderer->doc .= '</div>';
    }

    private function renderFields(Doku_Renderer &$renderer, $poll) {
        global $ID;
        $renderer->doc .= '<div class="list-group list-group-flush">';
        $renderer->doc .= '<div class="list-group-item"><a href="' . wl($this->getConf('archive-path')) . '">' . $this->getLang('archive') . '</a></div>';
        if ($this->helper->canEdit()) {
            $renderer->doc .= '<div class="list-group-item">
                <a href="' . wl($ID, [
                    'do' => helper_plugin_fkspoll::TARGET,
                    'poll[do]' => 'edit',
                    'question_id' => $poll['question_id']
                ]) . '">' . $this->getLang('edit_poll') . '
                </a>
                </div>';

            $renderer->doc .= '<div class="list-group-item"><a href="' . wl($ID, [
                    'do' => helper_plugin_fkspoll::TARGET,
                    'poll[do]' => 'edit',
                ]) . '">' . $this->getLang('create_poll') . '</a></div>';
        }
        $renderer->doc .= '</div>';
    }
}
