<?php
namespace phpjs\elements\html;

use phpjs\Text;

class HTMLTitleElement extends HTMLElement
{
    protected function __construct()
    {
        parent::__construct();
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'text':
                $value = '';

                foreach ($this->mChildNodes as $node) {
                    if ($node instanceof Text) {
                        $value .= $node->data;
                    }
                }

                return $value;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'text':
                parent::__set('textContent', $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }
}