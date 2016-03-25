<?php
namespace phpjs\elements;

use phpjs\Attr;
use phpjs\AttributeList;
use phpjs\ChildNode;
use phpjs\DOMTokenList;
use phpjs\exceptions\InUseAttributeError;
use phpjs\exceptions\NotFoundError;
use phpjs\GetElementsBy;
use phpjs\NamedNodeMap;
use phpjs\Namespaces;
use phpjs\Node;
use phpjs\NonDocumentTypeChildNode;
use phpjs\ParentNode;
use phpjs\urls\URLInternal;

/**
 * @see https://dom.spec.whatwg.org/#element
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element
 */
class Element extends Node implements \SplObserver
{
    use ChildNode;
    use GetElementsBy;
    use NonDocumentTypeChildNode;
    use ParentNode;

    protected $mNamedNodeMap;
    protected $mAttributesList;
    protected $mClassList; // ClassList
    protected $mEndTagOmitted;
    protected $mLocalName;
    protected $mNamespaceURI;
    protected $mPrefix;
    protected $mTagName;

    public function __construct($aLocalName, $aNamespaceURI, $aPrefix = null)
    {
        parent::__construct();
        $this->mAttributesList = new AttributeList();
        $this->mClassList = new DOMTokenList($this, 'class');
        $this->mEndTagOmitted = false;
        $this->mLocalName = strtolower($aLocalName);
        $this->mNamedNodeMap = new NamedNodeMap($this, $this->mAttributesList);
        $this->mNamespaceURI = $aNamespaceURI;
        $this->mNodeName = strtoupper($aLocalName);
        $this->mNodeType = self::ELEMENT_NODE;
        $this->mPrefix = $aPrefix;
        $this->mTagName = (!$this->mPrefix ? '' : $this->mPrefix . ':') .
            ($this->mOwnerDocument instanceof HTMLDocument ?
                strtoupper($aLocalName) : $aLocalName);
    }

    public function __get( $aName ) {
        switch ($aName) {
            case 'attributes':
                return $this->mNamedNodeMap;

            case 'childElementCount':
                return $this->getChildElementCount();

            case 'children':
                return $this->getChildren();

            case 'classList':
                return $this->mClassList;

            case 'className':
                return $this->reflectStringAttributeValue('class');

            case 'firstElementChild':
                return $this->getFirstElementChild();

            case 'id':
                return $this->reflectStringAttributeValue($aName);

            case 'innerHTML':
                $rv = '';

                foreach ($this->mChildNodes as $child) {
                    $rv .= $child->toHTML();
                }

                return $rv;

            case 'lastElementChild':
                return $this->getLastElementChild();

            case 'localName':
                return $this->mLocalName;

            case 'namespaceURI':
                return $this->mNamespaceURI;

            case 'nextElementSibling':
                return $this->getNextElementSibling();

            case 'prefix':
                return $this->mPrefix;

            case 'previousElementSibling':
                return $this->getPreviousElementSibling();

            case 'tagName':
                return $this->mTagName;

            default:
                return parent::__get($aName);
        }
    }

    public function __set($aName, $aValue)
    {
        switch ($aName) {
            case 'className':
                $this->_setAttributeValue('class', $aValue);

                break;

            case 'id':
                $this->_setAttributeValue($aName, $aValue);

                break;

            default:
                parent::__set($aName, $aValue);
        }
    }

    public function closest($aSelectorRule)
    {
        // TODO
    }

    /**
     * Retrieves the value of the attribute with the given name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattribute
     *
     * @param string $aName The name of the attribute whose value is to be
     *     retrieved.
     *
     * @return string|null
     */
    public function getAttribute($aName)
    {
        $attr = $this->mAttributesList->getAttrByName($aName, $this);

        return $attr ? $attr->value : null;
    }

    /**
     * Retrieves the attribute node with the given name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributenode
     *
     * @param string $aName The name of the attribute that is to be retrieved.
     *
     * @return Attr|null
     */
    public function getAttributeNode($aName)
    {
        return $this->mAttributesList->getAttrByName($aName);
    }

    /**
     * Retrieves the attribute node with the given namespace and local name, if
     * any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributenodens
     *
     * @param string $aNamespace The namespaceURI of the attribute node to be
     *     retrieved.
     *
     * @param string $aLocalName The localName of the attribute node to be
     *     retrieved.
     *
     * @return Attr|null
     */
    public function getAttributeNodeNS($aNamespace, $aLocalName)
    {
        return $this->mAttributesList->getAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName,
            $this
        );
    }

    /**
     * Retrieves the value of the attribute with the given namespace and local name, if any.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-getattributens
     *
     * @param  string       $aNamespace The namespaceURI of the attribute whose value is to be retrieved.
     * @param  string       $aLocalName The localName of the attribute whose value is to be retrieved.
     * @return string|null
     */
    public function getAttributeNS($aNamespace, $aLocalName)
    {
        return $this->mAttributesList->getAttrValue(
            $this,
            $aLocalName,
            $aNamespace
        );
    }

    /**
     * Returns true if the attribtue with the given name is present in the
     * Element's attribute list, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattribute
     *
     * @param string $aName The name of the attribute to find.
     *
     * @return bool
     */
    public function hasAttribute($aName)
    {
        return !!$this->mAttributesList->getAttrByName($aName, $this);
    }

    /**
     * Returns true if the attribute with the given namespace and localName
     * is present in the Element's attribute list, otherwise false.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param string $aNamespace The namespace of the attribute to find.
     *
     * @param string $aLocalName The localName of the attribute to find.
     *
     * @return bool
     */
    public function hasAttribueNS($aNamespace, $aLocalName)
    {
        return !!$this->mAttributesList->getAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName,
            $this
        );
    }

    /**
     * Returns true if there are attributes present in the Element's attribute
     * list, otherwise false.
     *
     * @return bool
     */
    public function hasAttributes()
    {
        return $this->mAttributesList->count() !== 0;
    }

    public function insertAdjacentHTML($aHTML) {
        // TODO
    }

    public function matches($aSelectorRule)
    {
        // TODO
    }

    /**
     * Removes an attribute with the specified name from the Element's attribute
     * list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-removeattribute
     *
     * @param string $aName The attributes name.
     */
    public function removeAttribute($aName)
    {
        $this->mAttributesList->removeAttrByName($aName, $this);
    }

    /**
     * Removes the given attribute from the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-removeattributenode
     *
     * @param Attr $aAttr The attribute to be removed.
     *
     * @return Attr The Attr node that was removed.
     *
     * @throws NotFoundError
     */
    public function removeAttributeNode(Attr $aAttr)
    {
        if (!$this->mAttributesList->hasAttr($aAttr)) {
            throw new NotFoundError();
        }

        $this->mAttributesList->removeAttr($aAttr, $this);

        return $aAttr;
    }

    /**
     * Removes the attribute with the given namespace and local name from the
     * Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-hasattributens
     *
     * @param string $aNamespace The namespaceURI of the attribute to be
     *     removed.
     *
     * @param string $aLocalName The localName of the attribute to be removed.
     */
    public function removeAttributeNS($aNamespace, $aLocalName)
    {
        $this->mAttributesList->removeAttrByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName,
            $this
        );
    }

    /**
     * Either adds a new attribute to the Element's attribute list or it
     * modifies the value of an already existing attribute with the the same
     * name.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattribute
     *
     * @param string $aQualifiedName  The name of the attribute.
     *
     * @param string $aValue The value of the attribute.
     */
    public function setAttribute($aQualifiedName, $aValue)
    {
        // TODO: If qualifiedName does not match the Name production in XML,
        // throw an InvalidCharacterError exception.

        if (
            $this->mNamespaceURI === Namespaces::HTML &&
            $this->mOwnerDocument instanceof HTMLDocument
        ) {
            $qualifiedName = strtolower($aQualifiedName);
        } else {
            $qualifiedName = $aQualifiedName;
        }

        $attribute = null;

        foreach ($this->mAttributesList as $attr) {
            if ($attr->name === $qualifiedName) {
                $attribute = $attr;
                break;
            }
        }

        if (!$attribute) {
            $attribute = new Attr($qualifiedName, $aValue);
            $this->mAttributesList->appendAttr($attribute, $this);
            return;
        }

        $this->mAttributesList->changeAttr($attr, $this, $aValue);
    }

    /**
     * Appends the given attribute to the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattributenode
     *
     * @param Attr $aAttr The attribute to be appended.
     */
    public function setAttributeNode(Attr $aAttr)
    {
        try {
            return $this->mAttributesList->setAttr($aAttr, $this);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Appends the given namespaced attribute to the Element's attribute list.
     *
     * @link https://dom.spec.whatwg.org/#dom-element-setattributenodens
     *
     * @param Attr $aAttr The namespaced attribute to be appended.
     */
    public function setAttributeNodeNS(Attr $aAttr)
    {
        try {
            return $this->mAttributesList->setAttr($aAttr, $this);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Either appends a new attribute or modifies the value of an existing
     * attribute with the given namespace and name.
     *
     * @param string $aNamespace The namespaceURI of the attribute.
     *
     * @param string $aName The name of the attribute.
     *
     * @param string $aValue The value of the attribute.
     */
    public function setAttributeNS($aNamespace, $aName, $aValue)
    {
        try {
            $parts = Namespaces::validateAndExtract($aNamespace, $aName);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->mAttributesList->setAttrValue(
            $this,
            $parts['localName'],
            $aValue,
            $parts['prefix'],
            $parts['namespace']
        );
    }

    public function toHTML()
    {
        $html = '';

        switch ($this->mNodeType) {
            case self::ELEMENT_NODE:
                $tagName = strtolower($this->mNodeName);
                $html = '<' . $tagName;

                foreach($this->mAttributesList as $attribute) {
                    $html .= ' ' . $attribute->name;

                    if (!Attr::_isBool($attribute->name)) {
                        $html .= '="' . $attribute->value . '"';
                    }
                }

                $html .= '>';

                foreach($this->mChildNodes as $child) {
                    $html .= $child->toHTML();
                }

                if (!$this->mEndTagOmitted) {
                    $html .= '</' . $tagName . '>';
                }

                break;

            case self::TEXT_NODE:
                $html = $this->textContent;

                break;

            case self::PROCESSING_INSTRUCTION_NODE:
                // TODO
                break;

            case self::COMMENT_NODE:
                $html = '<!-- ' . $this->textContent . ' -->';

                break;

            case self::DOCUMENT_TYPE_NODE:
                // TODO
                break;

            case self::DOCUMENT_NODE:
            case self::DOCUMENT_FRAGMENT_NODE:
                foreach ($this->mChildNodes as $child) {
                    $html .= $child->toHTML();
                }

                break;

            default:
                # code...
                break;
        }

        return $html;
    }

    public function update(\SplSubject $aObject)
    {

    }

    /**
     * Appends an Attr node to the Element's attribute list.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-append
     *
     * @param  Attr   $aAttr The Attr node to be appended.
     */
    public function _appendAttribute(Attr $aAttr)
    {
        // TODO: Queue a mutation record for "attributes"
        $this->mAttributesList[] = $aAttr;
        $aAttr->_setOwnerElement($this);

        $this->attributeHookHandler('set', $aAttr);
        $this->attributeHookHandler('added', $aAttr);
    }

    /**
     * Changes the value of an attribute.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-change
     *
     * @param Attr $aAttr The Attr whose value is to be changed.
     *
     * @param string $aValue The new value of the given Attr.
     */
    public function _changeAttributeValue(Attr $aAttr, $aValue)
    {
        // TODO: Queue a mutation record for "attributes"

        $aAttr->setValue($aValue);
        $this->attributeHookHandler('set', $aAttr);
        $this->attributeHookHandler('changed', $aAttr);
    }

    /**
     * Returns the first Attr in the Element's attribute list that has the given
     * name.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-get-by-name
     *
     * @param string $aName The name of the attribute to find.
     *
     * @return Attr|null
     */
    public function _getAttributeByName($aName)
    {
        $name = $aName;

        if ($this->mNamespaceURI === Namespaces::HTML &&
            $this->mOwnerDocument instanceof HTMLDocument) {
            $name = strtolower($aName);
        }

        foreach ($this->mAttributesList as $attr) {
            if (strcmp($attr->name, $name) === 0) {
                return $attr;
            }
        }

        return null;
    }

    /**
     * Returns the first Attr in the Element's attribute list that has the given
     * namespace and local name.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-get-by-namespace
     *
     * @param string $aNamespace The namespaceURI of the attribute to find.
     *
     * @param string $aLocalName The localName of the attribute to find.
     *
     * @return Attr|null
     */
    public function _getAttributeByNamespaceAndLocalName(
        $aNamespace,
        $aLocalName
    ) {
        $namespace = $aNamespace === '' ? null : $aNamespace;

        foreach ($this->mAttributesList as $attr) {
            if (strcmp($attr->namespaceURI, $namespace) === 0 &&
                strcmp($attr->localName, $aLocalName) === 0) {
                return $attr;
            }
        }

        return null;
    }

    public function _isEndTagOmitted()
    {
        return $this->mEndTagOmitted;
    }

    /**
     * Removes the given Attr from the Element's attribute list.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-remove
     *
     * @param  Attr $aAttr The Attr to be removed.
     */
    public function _removeAttribute(Attr $aAttr)
    {
        // TODO: Queue a mutation record for "attributes"
        $index = array_search($aAttr, $this->mAttributesList);

        if ($index !== false) {
            array_splice($this->mAttributesList, $index, 1);
            $aAttr->setOwnerElement(null);
            $this->attributeHookHandler('removed', $aAttr);
        }
    }

    /**
     * Removes the attribute with the given name from the Element's attribute
     * list.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-name
     *
     * @param string $aName The name of the attribute to be removed.
     *
     * @return Attr|null
     */
    public function _removeAttributeByName($aName)
    {
        $attr = $this->_getAttributeByName($aName);

        if ($attr) {
            $this->_removeAttribute($attr);
        }

        return $attr;
    }

    /**
     * Removes the attribtue with the given namespace and local name from the
     * Element's attribute list.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-remove-by-namespace
     *
     * @param string $aNamespace The namespaceURI of the attribute to be
     *     removed.
     *
     * @param string $aLocalName The localName of the attribute to be removed.
     *
     * @return Attr|null
     */
    public function _removeAttributeByNamespaceAndLocalName(
        $aNamespace,
        $aLocalName
    ) {
        $attr = $this->_getAttributeByNamespaceAndLocalName(
            $aNamespace,
            $aLocalName
        );

        if ($attr) {
            $this->_removeAttribute($attr);
        }

        return $attr;
    }

    /**
     * Associates an Attr with this Element.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-set
     *
     * @param Attr $aAttr The Attr to be appended to this Element's attribute
     *     list.
     *
     * @param string $aNamespace Optional. Whether or not the attribute being
     *     appended is namespaced.
     *
     * @param string $aLocalName Optional. Whether or not the attribute being
     *     appended is namespaced.
     */
    public function _setAttribute(
        Attr $aAttr,
        $aNamespace = null,
        $aLocalName = null
    ) {
        if (
            $aAttr->ownerElement !== null &&
            !($aAttr->ownerElement instanceof Element)
        ) {
            throw new InUseAttributeError();
        }

        $oldAttr = null;

        if ($aNamespace && $aLocalName) {
            $oldAttr = $this->_getAttributeByNamespaceAndLocalName(
                $attr->namespaceURI,
                $attr->localName
            );
        } else {
            $oldAttr = $this->_getAttributeByName($aAttr->name);
        }

        if ($oldAttr === $aAttr) {
            return $aAttr;
        }

        if ($oldAttr) {
            $this->_removeAttribute($oldAttr);
        }

        $this->_appendAttribute($aAttr);

        return $oldAttr;
    }

    /**
     * Sets an attributes value.
     *
     * @internal
     *
     * @link https://dom.spec.whatwg.org/#concept-element-attributes-set-value
     *
     * @param string $aLocalName The localName of the attribute to find.
     *
     * @param string $aValue The value of the attribute.
     *
     * @param string $aName The name of the attribute.
     *
     * @param string $aPrefix The namespace prefix of the attribute.
     *
     * @param string $aNamespace The namespaceURI of the attribute to find.
     */
    public function _setAttributeValue(
        $aLocalName,
        $aValue,
        $aPrefix = null,
        $aNamespace = null
    ) {
        $prefix = !$aPrefix ? null : $aPrefix;
        $namespace = !$aNamespace ? null : $aNamespace;

        $attr = $this->_getAttributeByNamespaceAndLocalName(
            $namespace,
            $aLocalName
        );

        if (!$attr) {
            $attr = new Attr($aLocalName, $aValue, $namespace, $prefix);
            $this->_appendAttribute($attr);
            return;
        }

        $this->_changeAttributeValue($attr, $aValue);
    }

    public function attributeHookHandler($aHookType, Attr $aAttr)
    {
        switch ($aAttr->name) {
            case 'class':
                if ($aHookType == 'set') {
                    $value = $aAttr->value;

                    if (!empty($value)) {
                        $this->mClassList->appendTokens(
                            DOMTokenList::_parseOrderedSet($value)
                        );
                    }
                } elseif ($aHookType == 'removed') {
                    $this->mClassList->emptyList();
                }
        }
    }

    protected function reflectURLAttributeValue(
        $aName,
        $aMissingValueDefault = null
    ) {
        $attr = $this->_getAttributeByNamespaceAndLocalName(null, $aName);

        if ($attr) {
            $url = $this->resolveURL($attr->value, self::$mBaseURI);

            if ($url !== false) {
                return $url['serialized_url'];
            }
        } elseif ($aMissingValueDefault !== null) {
            return $aMissingValueDefault;
        }

        return '';
    }

    /**
     * Gets the value of an attribute that is to be reflected as an object
     * property.
     *
     * @link https://dom.spec.whatwg.org/#concept-reflect
     *
     * @param string $aName The name of the attribute that is to be reflected.
     *
     * @return string
     */
    protected function reflectStringAttributeValue($aName)
    {
        $attr = $this->_getAttributeByNamespaceAndLocalName(null, $aName);

        return !$attr ? '' : $attr->value;
    }

    /**
     * Resolves a URL to the absolute URL that it implies.
     *
     * @link https://html.spec.whatwg.org/multipage/infrastructure.html#resolve-a-url
     *
     * @internal
     *
     * @param string $aUrl A URL string to be resolved.
     *
     * @param string $aBase Optional argument that should be an absolute URL
     *     that a relative URL can be resolved against.  Default is null.
     *
     * @return mixed[]|bool An array containing the serialized absolute URL as
     *     well as the parsed URL or false on failure.
     */
    protected function resolveURL($aUrl, URLInternal $aBase = null)
    {
        $url = $aUrl;
        $base = null;

        // TODO: Handle encoding
        $encoding = 'utf-8';

        if ($aBase && $aBase->isFlagSet(URLInternal::FLAG_NON_RELATIVE)) {
            $base = $aBase;
        } else {
            $base = self::$mBaseURI;
        }

        $parsedURL = URLInternal::URLParser($url, $base, $encoding);

        if ($parsedURL === false) {
            // Abort these steps.  The URL cannot be resolved.
            return false;
        }

        $serializedURL = $parsedURL->serializeURL();

        return array(
            'absolute_url' => $serializedURL,
            'parsed_url' => $parsedURL
        );
    }

    /**
     * Returns an array of Elements with the specified tagName that are
     * immediate children of the parent.
     *
     * @param string $aTagName The tagName to search for.
     *
     * @return Element[] A list of Elements with the specified tagName.
     */
    protected function shallowGetElementsByTagName($aTagName)
    {
        $collection = array();
        $node = $this->mFirstChild;
        $tagName = strtoupper($aTagName);

        while ($node) {
            if (strcmp($node->tagName, $tagName) == 0) {
                $collection[] = $node;
            }

            $node = $node->nextElementSibling;
        }

        return $collection;
    }
}
