<?php

namespace Rowbot\DOM\Tests\dom\nodes;

use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Tests\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/nodes/ChildNode-after.html
 */
class ChildNodeAfterTest extends TestCase
{
    private static $document;

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithoutArguments($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->after();

        $this->assertSame($innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithNull($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->after(null);

        $this->assertSame($innerHTML . 'null', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithEmptyString($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->after('');

        $this->assertSame('', $parent->lastChild->data);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithStringText($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->after('text');

        $this->assertSame($innerHTML . 'text', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithOneElement($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($child);
        $child->after($x);

        $this->assertSame($innerHTML . '<x></x>', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithOneElementAndText($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($child);
        $child->after($x, 'text');

        $this->assertSame($innerHTML . '<x></x>text', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithContextObject($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $parent->appendChild($child);
        $child->after('text', $child);

        $this->assertSame('text' . $innerHTML, $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithContextObjectAndNodeSwitchingPositions($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $parent->appendChild($x);
        $parent->appendChild($child);
        $child->after($child, $x);

        $this->assertSame($innerHTML . '<x></x>', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithAllSiblingsOfChild($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $z = self::$document->createElement('z');
        $parent->appendChild($y);
        $parent->appendChild($child);
        $parent->appendChild($x);
        $child->after($x, $y, $z);

        $this->assertSame($innerHTML . '<x></x><y></y><z></z>', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithSomeSiblings1($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $z = self::$document->createElement('z');
        $parent->appendChild($child);
        $parent->appendChild($x);
        $parent->appendChild($y);
        $parent->appendChild($z);
        $child->after($x, $y);

        $this->assertSame($innerHTML . '<x></x><y></y><z></z>', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithSomeSiblings2($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $v = self::$document->createElement('v');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $z = self::$document->createElement('z');
        $parent->appendChild($child);
        $parent->appendChild($v);
        $parent->appendChild($x);
        $parent->appendChild($y);
        $parent->appendChild($z);
        $child->after($v, $x);

        $this->assertSame($innerHTML . '<v></v><x></x><y></y><z></z>', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWhenPreinsertBehavesLikeAppend($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $parent->appendChild($child);
        $parent->appendChild($x);
        $parent->appendChild($y);
        $child->after($y, $x);

        $this->assertSame($innerHTML . '<y></y><x></x>', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterWithOneSiblingAndText($child, string $nodeName, string $innerHTML): void
    {
        $parent = self::$document->createElement('div');
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $parent->appendChild($child);
        $parent->appendChild($x);
        $parent->appendChild(self::$document->createTextNode('1'));
        $parent->appendChild($y);
        $child->after($x, '2');

        $this->assertSame($innerHTML . '<x></x>21<y></y>', $parent->innerHTML);
    }

    /**
     * @dataProvider nodeProvider
     */
    public function testAfterOnChildWithoutParent($child, string $nodeName, string $innerHTML): void
    {
        $x = self::$document->createElement('x');
        $y = self::$document->createElement('y');
        $x->after($y);

        $this->assertNull($x->nextSibling);
    }

    public function nodeProvider(): array
    {
        $document = self::loadDocument();

        return [
            [$document->createComment('test'), 'Comment', '<!--test-->'],
            [$document->createElement('test'), 'Element', '<test></test>'],
            [$document->createTextNode('test'), 'Text', 'test'],
        ];
    }

    public static function loadDocument(): Document
    {
        if (self::$document) {
            return self::$document;
        }

        $html = <<<'TEST_HTML'
<!DOCTYPE html>
<meta charset=utf-8>
<title>ChildNode.after</title>
<link rel=help href="https://dom.spec.whatwg.org/#dom-childnode-after">
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<script>

function test_after(child, nodeName, innerHTML) {

    test(function() {
        var parent = document.createElement('div');
        parent.appendChild(child);
        child.after();
        assert_equals(parent.innerHTML, innerHTML);
    }, nodeName + '.after() without any argument.');

    test(function() {
        var parent = document.createElement('div');
        parent.appendChild(child);
        child.after(null);
        var expected = innerHTML + 'null';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with null as an argument.');

    test(function() {
        var parent = document.createElement('div');
        parent.appendChild(child);
        child.after(undefined);
        var expected = innerHTML + 'undefined';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with undefined as an argument.');

    test(function() {
        var parent = document.createElement('div');
        parent.appendChild(child);
        child.after('');
        assert_equals(parent.lastChild.data, '');
    }, nodeName + '.after() with the empty string as an argument.');

    test(function() {
        var parent = document.createElement('div');
        parent.appendChild(child);
        child.after('text');
        var expected = innerHTML + 'text';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with only text as an argument.');

    test(function() {
        var parent = document.createElement('div');
        var x = document.createElement('x');
        parent.appendChild(child);
        child.after(x);
        var expected = innerHTML + '<x></x>';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with only one element as an argument.');

    test(function() {
        var parent = document.createElement('div');
        var x = document.createElement('x');
        parent.appendChild(child);
        child.after(x, 'text');
        var expected = innerHTML + '<x></x>text';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with one element and text as arguments.');

    test(function() {
        var parent = document.createElement('div');
        parent.appendChild(child);
        child.after('text', child);
        var expected = 'text' + innerHTML;
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with context object itself as the argument.');

    test(function() {
        var parent = document.createElement('div')
        var x = document.createElement('x');
        parent.appendChild(x);
        parent.appendChild(child);
        child.after(child, x);
        var expected = innerHTML + '<x></x>';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with context object itself and node as the arguments, switching positions.');

    test(function() {
        var parent = document.createElement('div');
        var x = document.createElement('x');
        var y = document.createElement('y');
        var z = document.createElement('z');
        parent.appendChild(y);
        parent.appendChild(child);
        parent.appendChild(x);
        child.after(x, y, z);
        var expected = innerHTML + '<x></x><y></y><z></z>';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with all siblings of child as arguments.');

    test(function() {
        var parent = document.createElement('div')
        var x = document.createElement('x');
        var y = document.createElement('y');
        var z = document.createElement('z');
        parent.appendChild(child);
        parent.appendChild(x);
        parent.appendChild(y);
        parent.appendChild(z);
        child.after(x, y);
        var expected = innerHTML + '<x></x><y></y><z></z>';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.before() with some siblings of child as arguments; no changes in tree; viable sibling is first child.');

    test(function() {
        var parent = document.createElement('div')
        var v = document.createElement('v');
        var x = document.createElement('x');
        var y = document.createElement('y');
        var z = document.createElement('z');
        parent.appendChild(child);
        parent.appendChild(v);
        parent.appendChild(x);
        parent.appendChild(y);
        parent.appendChild(z);
        child.after(v, x);
        var expected = innerHTML + '<v></v><x></x><y></y><z></z>';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with some siblings of child as arguments; no changes in tree.');

    test(function() {
        var parent = document.createElement('div');
        var x = document.createElement('x');
        var y = document.createElement('y');
        parent.appendChild(child);
        parent.appendChild(x);
        parent.appendChild(y);
        child.after(y, x);
        var expected = innerHTML + '<y></y><x></x>';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() when pre-insert behaves like append.');

    test(function() {
        var parent = document.createElement('div');
        var x = document.createElement('x');
        var y = document.createElement('y');
        parent.appendChild(child);
        parent.appendChild(x);
        parent.appendChild(document.createTextNode('1'));
        parent.appendChild(y);
        child.after(x, '2');
        var expected = innerHTML + '<x></x>21<y></y>';
        assert_equals(parent.innerHTML, expected);
    }, nodeName + '.after() with one sibling of child and text as arguments.');

    test(function() {
        var x = document.createElement('x');
        var y = document.createElement('y');
        x.after(y);
        assert_equals(x.nextSibling, null);
    }, nodeName + '.after() on a child without any parent.');
}

test_after(document.createComment('test'), 'Comment', '<!--test-->');
test_after(document.createElement('test'), 'Element', '<test></test>');
test_after(document.createTextNode('test'), 'Text', 'test');

</script>
</html>
TEST_HTML;

        $parser = new DOMParser();
        self::$document = $parser->parseFromString($html, 'text/html');

        return self::$document;
    }

    public static function tearDownAfterClass(): void
    {
        self::$document = null;
    }
}
