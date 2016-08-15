<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Michal Červeňák <miso@fykos.cz>
 */
if(!defined('DOKU_INC')){
    die();
}
if(!defined('DOKU_PLUGIN')){
    define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
}

class syntax_plugin_fkspoll_poll extends DokuWiki_Syntax_Plugin {

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
        return array();
    }

    public function getSort() {
        return 24;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~FKSPOLL-?[a-z]*?~~',$mode,'plugin_fkspoll_poll');
    }

    /**
     * Handle the match
     */
    public function handle($match,$state) {
        global $conf;
        $matches = array();
        if(preg_match('/~~FKSPOLL(-([a-z]*?))?~~/',$match,$matches)){
            list(,,$lang) = $matches;
        }else{
            $lang = $conf['lang'];
        }
        return array($state,array('lang' => $lang));
    }

    public function render($mode,Doku_Renderer &$renderer,$data) {
        list(,$param) = $data;
        global $ID;
        if($mode == 'xhtml'){
            $renderer->nocache();
            $renderer->doc.= '<div class="polls">';
            $polls = $this->helper->getCurrentPolls($param['lang']);
            foreach ($polls as $poll) {
                $renderer->doc.='<div class="poll">';
                $renderer->doc.= $this->helper->renderPoll($poll);
                $renderer->doc.= '<a href="'.wl('anketa').'"><span class="btn">'.$this->getLang('archive').'</span></a>';
                if($this->helper->canEdit()){
                    $renderer->doc.='<a href="'.wl($ID,array('do' => 'fkspoll_edit','question_id' => $poll['question_id'])).'"><span class="btn">'.$this->getLang('edit_poll').'</span></a>';
                    $renderer->doc.='<a href="'.wl($ID,array('do' => 'fkspoll_edit')).'"><span class="btn">'.$this->getLang('create_poll').'</span></a>';
                }
                $renderer->doc.='</div>';
            }
            $renderer->doc.='</div>';
        }

        return false;
    }

}
