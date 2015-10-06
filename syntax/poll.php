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
        $this->Lexer->addSpecialPattern('~~FKSPOLL-?[a-z]*?~~',$mode,'plugin_fkspoll_poll');
    }

    /**
     * Handle the match
     */
    public function handle($match,$state) {
        global $conf;
        $matches=array();
        if(preg_match('/~~FKSPOLL-([a-z]*?)~~/',$match,$matches)){
            list(,$lang)=$matches;
        }else{           
            $lang=$conf['lang'];
        }
        return array($state,array('lang'=>$lang));
    }

    public function render($mode,Doku_Renderer &$renderer,$data) {
        list(,$param) = $data;
        
        if($mode == 'xhtml'){
            $renderer->nocache();
            $renderer->doc.= '<div class="FKS_poll">';
            $renderer->doc.='<div class="poll">';
            
            if(auth_quickaclcheck('start') >= 4){
                $renderer->doc.='<span>Maybe not working</span>';
                $renderer->doc.='<a href="?do=admin&page=fkspoll"><button>'.$this->getLang('create_poll').'</button></a>';
            }
            $poll = $this->helper->getCurrentPoll($param['lang']);
            $renderer->doc.= $this->helper->getHtml($poll);
            $renderer->doc.= '<a href="'.wl('anketa').'">'.$this->getLang('archive').'</a>';
            $renderer->doc.='</div>';
            $renderer->doc.='</div>';
        }

        return false;
    }

    


}
