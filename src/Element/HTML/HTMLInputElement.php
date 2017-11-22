<?php
namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\HTML\Support\{
    Listable,
    Resettable
};

/**
 * @see https://html.spec.whatwg.org/multipage/forms.html#the-input-element
 */
class HTMLInputElement extends HTMLElement implements Listable, Resettable
{
    protected function __construct()
    {
        parent::__construct();
    }
}
