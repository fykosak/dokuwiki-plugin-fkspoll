<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michal Červeňák <miso@fykos.cz>
 */
// must be run within Dokuwiki

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
        return array('formatting','substition','disabled');
    }

    public function getSort() {
        return 24;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~FKSPOLL-ARCHIVE~~',$mode,'plugin_fkspoll_archive');
    }

    /**
     * Handle the match
     */
    public function handle($match,$state) {


     

        return array($state,array());
    }

    public function render($mode,Doku_Renderer &$renderer,$data) {
       // list(,$m) = $data;
        //list($polls) = $m;
        if($mode == 'xhtml'){

            $renderer->nocache();
                $polls = $this->helper->AllPolls();
            $renderer->doc.= '<h1>všetky ankety</h1>';
            foreach (array_reverse($polls) as $poll) {
                $renderer->doc.= $this->helper->getClosedPollHtml($poll,true);
            }
        }
       
        if($mode=='metadata'){
           
        }

        return false;
    }

}
