<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

/**
 * Represents the HTML <meta> element.
 *
 * @see https://html.spec.whatwg.org/multipage/semantics.html#the-meta-element
 *
 * @property string $content   Reflects the value of the HTML content attribute. Contains the value part of a name =>
 *                             value pair when the name attribute is present.
 * @property string $httpEquiv Reflects the value of the HTML http-equiv attribute.
 * @property string $name      Reflects the value of the HTML name attribute.
 */
class HTMLMetaElement extends HTMLElement
{
    public function __get(string $name)
    {
        switch ($name) {
            case 'content':
                return $this->reflectStringAttributeValue($name);

            case 'httpEquiv':
                return $this->reflectStringAttributeValue('http-equiv');

            case 'name':
                return $this->reflectStringAttributeValue($name);

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'content':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            case 'httpEquiv':
                $this->attributeList->setAttrValue('http-equiv', (string) $value);

                break;

            case 'name':
                $this->attributeList->setAttrValue($name, (string) $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
