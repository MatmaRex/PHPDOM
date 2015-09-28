<?php
// https://developer.mozilla.org/en-US/docs/Web/API/CharacterData
// https://dom.spec.whatwg.org/#characterdata

require_once 'Node.class.php';
require_once 'ChildNode.class.php';
require_once 'NonDocumentTypeChildNode.class.php';

abstract class CharacterData extends Node {
    use ChildNode, NonDocumentTypeChildNode;

    protected $mData;

    public function __construct() {
        parent::__construct();

        $this->mData = '';
    }

    public function __get($aName) {
        switch ($aName) {
            case 'data':
                return $this->mData;
            case 'length':
                return strlen($this->mData);
            case 'nextElementSibling':
                return $this->getNextElementSibling();
            case 'previousElementSibling':
                return $this->getPreviousElementSibling();
            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'data':
                if (is_string($aValue)) {
                    $this->mData = $aValue;
                }

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function appendData($aData) {
        // TODO
    }

    public function deleteData($aOffset, $aCount) {
        // TODO
    }

    public function insertData($aOffset, $aData) {
        // TODO
    }

    public function replaceData($aOffset, $aCount, $aData) {
        // TODO
    }

    public function substringData($aOffset, $aCount) {
        // TODO
    }
}
