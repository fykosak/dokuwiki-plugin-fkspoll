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
        return array('formatting','substition','disabled');
    }

    public function getSort() {
        return 24;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~FKSPOLL~~',$mode,'plugin_fkspoll_poll');
    }

    /**
     * Handle the match
     */
    public function handle($match,$state) {
        

        


        return array($state,array());
    }

    public function render($mode,Doku_Renderer &$renderer,$data) {
        //list(,$m) = $data;
       // list($poll) = $m;
        if($mode == 'xhtml'){
           
            $renderer->nocache();
            $poll = $this->helper->getCurrentPoll();
            $renderer->doc.= $this->helper->getHtml($poll);
        }

        return false;
    }

    


}
