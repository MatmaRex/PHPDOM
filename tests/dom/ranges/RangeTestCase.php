<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Tests\TestCase;

abstract class RangeTestCase extends TestCase
{
    public static function getHtmlBaseDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'html';
    }
}
