<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Element\HTML\Support\Listable;
use Rowbot\DOM\Element\HTML\Support\Resettable;
use Rowbot\DOM\Element\HTML\Support\Submittable;

/**
 * @see https://html.spec.whatwg.org/multipage/forms.html#the-input-element
 */
class HTMLInputElement extends HTMLElement implements Listable, Resettable, Submittable
{
}
