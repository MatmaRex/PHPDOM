<?php

declare(strict_types=1);

namespace Rowbot\DOM\Element\HTML;

use Rowbot\DOM\Text;

class HTMLTitleElement extends HTMLElement
{
    public function __get(string $name)
    {
        switch ($name) {
            case 'text':
                $value = '';

                foreach ($this->childNodes as $node) {
                    if ($node instanceof Text) {
                        $value .= $node->data;
                    }
                }

                return $value;

            default:
                return parent::__get($name);
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'text':
                parent::__set('textContent', $value);

                break;

            default:
                parent::__set($name, $value);
        }
    }
}
