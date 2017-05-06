<?php

namespace fks\Form;

class CheckableElement extends \dokuwiki\Form\CheckableElement {
    public function toHTML() {
        if ($this->label) {
            return '<label ' . buildAttributes($this->label->attrs()) . '>' . DOKU_LF . $this->mainElementHTML() . DOKU_LF . '<span>' . hsc($this->label->val()) . '</span>' . DOKU_LF . '</label>';
        } else {
            return $this->mainElementHTML();
        }
    }
}
