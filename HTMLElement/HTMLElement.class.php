<?php
namespace phpjs;

require_once 'Element.class.php';

class HTMLElement extends Element {
    // state => array(keyword[, keyword, ...])
    const CONTENT_EDITABLE_STATE_MAP = ['true' => ['', 'true'], 'false' => ['false']];
    const CORS_STATE_MAP = ['Anonymous' => ['', 'anonymous'], 'Use Credentials' => ['use-credentials']];
    const DIR_STATE_MAP = ['ltr' => ['ltr'], 'rtl' => ['rtl'], 'auto' => ['auto']];
    const DRAGGABLE_STATE_MAP = ['true' => ['true'], 'false' => ['false']];
    const LANG_STATE_MAP = [];
    const SPELL_CHECK_STATE_MAP = ['true' => ['', 'true'], 'false' => ['false']];
    const TRANSLATE_STATE_MAP = ['yes' => ['', 'yes'], 'no' => ['no']];

    protected $mDataset;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null) {
        parent::__construct($aLocalName, $aNamespaceURI, $aPrefix);

        $this->mDataset;
    }

    public function __get($aName) {
        switch ($aName) {
            case 'accessKey':
            case 'accessKeyLabel':
                // For the time being, have accessKeyLabel return the same value as accessKey
                return $this->reflectStringAttributeValue('accessKey');
            case 'contentEditable':
                $state = $this->getAttributeStateEnumeratedString($aName, 'inherit', 'inherit', self::CONTENT_EDITABLE_STATE_MAP);
                // TODO: Check the contentEditable state of all parent elements if state == inherit to get a more accurate answer
                return $state;
            case 'dataset':
                return $this->mDataset;
            case 'dir':
                return $this->getAttributeStateEnumeratedString($aName, null, null, self::DIR_STATE_MAP);
            case 'draggable':
                $state = $this->getAttributeStateEnumeratedString($aName, null, 'auto', self::DRAGGABLE_STATE_MAP);

                return $state == 'true' ? true : false;
            case 'dropzone':
                return $this->reflectStringAttributeValue($aName);
            case 'hidden':
                return $this->reflectBooleanAttributeValue($aName);
            case 'isContentEditable':
                $state = $this->getAttributeStateEnumeratedString($aName, 'inherit', 'inherit', self::CONTENT_EDITABLE_STATE_MAP);
                // TODO: Check the contentEditable state of all parent elements if state == inherit to get a more accurate answer
                return $state == 'true' ? true : false;
            case 'lang':
                return $this->reflectStringAttributeValue($aName);
            case 'spellcheck':
                $state = $this->getAttributeStateEnumeratedString($aName, 'default', 'default', self::SPELL_CHECK_STATE_MAP);

                if ($state == 'true') {
                    $value = true;
                } else if ($state == 'false') {
                    $value = false;
                } else {
                    // TODO: Handle default states
                    return false;
                }

                return $value;
            case 'tabIndex':
                $index = filter_var($this->reflectStringAttributeValue('tabindex'), FILTER_VALIDATE_INT, [
                    'default' => 0
                ]);

                return $index;
            case 'title':
                return $this->reflectStringAttributeValue($aName);
            case 'translate':
                $state = $this->getAttributeStateEnumeratedString($aName, 'inherit', 'inherit', self::TRANSLATE_STATE_MAP);
                // TODO: Check the translate state of all parent elements to get a more accurate answer
                return $state == 'yes' ? true : false;
            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue) {
        switch ($aName) {
            case 'accessKey':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'contentEditable':
                if (strcasecmp($aValue, 'inherit') === 0) {
                    $this->_removeAttributeByNamespaceAndLocalName(null, $aName);
                } else if (strcasecmp($aValue, 'true') === 0 || strcasecmp($aValue, 'false') === 0) {
                    $this->_setAttributeValue($aName, $aValue);
                } else {
                    throw new \SyntaxError('The value must be one of "true", "false", or "inherit".');
                }

                break;

            case 'dir':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'draggable':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'dropzone':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'hidden':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'lang':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'spellcheck':
                $this->_setAttributeValue($aName, ($aValue === true ? 'true' : 'false'));

                break;

            case 'tabIndex':
                $this->_setAttributeValue('tabindex', $aValue);

                break;

            case 'title':
                $this->_setAttributeValue($aName, $aValue);

                break;

            case 'translate':
                $this->_setAttributeValue($aName, ($aValue === true ? 'yes' : 'no'));

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function __toString() {
        return get_class($this);
    }

    protected function reflectBooleanAttributeValue($aName) {
        $attr = $this->_getAttributeByNamespaceAndLocalName(null, $aName);

        return !!$attr;
    }

    protected function reflectEnumeratedStringAttributeValue($aName, $aMissingValueDefault = null) {
        $attr = $this->_getAttributeByNamespaceAndLocalName(null, $aName);

        if (!$attr && $aMissingValueDefault !== null) {
            return null;
        }

        return $attr ? $attr->value : '';
    }

    protected function getAttributeStateEnumeratedString($aName, $aInvalidValueDefault = null, $aMissingValueDefault = null, array $aStateMap = array()) {
        $attr = $this->_getAttributeByNamespaceAndLocalName(null, $aName);
        $state = null;

        if ($attr) {
            foreach ($aStateMap as $attributeState => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strcasecmp($attr->value, $keyword) === 0) {
                        $state = $attributeState;
                        break 2;
                    }
                }
            }

            if ($state === null) {
                if ($aInvalidValueDefault !== null) {
                    $state = $aInvalidValueDefault;
                } else if ($aMissingValueDefault !== null) {
                    $state = $aMissingValueDefault;
                }
            }
        } else if (!$attr && $aMissingValueDefault !== null) {
            $state = $aMissingValueDefault;
        }

        return $state !== null ? $state : '';
    }
}
