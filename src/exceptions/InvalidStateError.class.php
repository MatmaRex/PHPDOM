<?php
namespace phpjs\exceptions;

/**
 * @see https://heycam.github.io/webidl/#invalidstateerror
 */
class InvalidStateError extends DOMException
{
    public function __construct($message = '', $previous = null)
    {
        if ($message === '') {
            $message = 'This object is in an invalid state.';
        }

        parent::__construct($message, 11, $previous);
    }
}