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

class syntax_plugin_fkspoll_archive extends DokuWiki_Syntax_Plugin {

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
        $this->Lexer->addSpecialPattern('~~FKSPOLL-ARCHIVE-?[a-z]*?~~',$mode,'plugin_fkspoll_archive');
    }

    /**
     * Handle the match
     */
    public function handle($match,$state) {
        global $conf;
        $matches = array();
        if(preg_match('/~~FKSPOLL-ARCHIVE-([a-z]*?)~~/',$match,$matches)){
            list(,$lang) = $matches;
        }else{

            $lang = $conf['lang'];
        } return array($state,array('lang' => $lang));
    }

    public function render($mode,Doku_Renderer &$renderer,$data) {
        list(,$param) = $data;
        if($mode == 'xhtml'){

            $renderer->nocache();
            $polls = $this->helper->AllPolls($param['lang']);
            //$renderer->doc.= '<h1>všetky ankety</h1>';
            $renderer->doc.= '<div class="polls">';
            foreach (array_reverse($polls) as $poll) {
                $renderer->doc.='<div class="poll">';
                $renderer->doc.= $this->helper->GetClosedPollHtml($poll,true);
                $renderer->doc.='</div>';
            }
            $renderer->doc.='</div>';
        }

        if($mode == 'metadata'){
            
        }

        return false;
    }

}
