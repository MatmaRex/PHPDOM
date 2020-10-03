<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Rowbot\DOM\Range;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-intersectsNode-2.html
 */
class RangeIntersectsNode2Test extends RangeTestCase
{
    public function testIntersectsNode(): void
    {
        $document = self::$document;
        $range = new Range();
        $div = $document->getElementById('div');
        $s0 = $document->getElementById('s0');
        $s1 = $document->getElementById('s1');
        $s2 = $document->getElementById('s2');

        // Range encloses s0
        $range->setStart($div, 0);
        $range->setEnd($div, 1);

        $this->assertTrue($range->intersectsNode($s0));
        $this->assertFalse($range->intersectsNode($s1));
        $this->assertFalse($range->intersectsNode($s2));

        // Range encloses s1
        $range->setStart($div, 1);
        $range->setEnd($div, 2);

        $this->assertFalse($range->intersectsNode($s0));
        $this->assertTrue($range->intersectsNode($s1));
        $this->assertFalse($range->intersectsNode($s2));

        // Range encloses s2
        $range->setStart($div, 2);
        $range->setEnd($div, 3);

        $this->assertFalse($range->intersectsNode($s0));
        $this->assertFalse($range->intersectsNode($s1));
        $this->assertTrue($range->intersectsNode($s2));
    }

    public static function fetchDocument(): string
    {
        return <<<'TEST_HTML'
<!doctype htlml>
<title>Range.intersectsNode</title>
<script src="/resources/testharness.js"></script>
<script src="/resources/testharnessreport.js"></script>
<div id="div"><span id="s0">s0</span><span id="s1">s1</span><span id="s2">s2</span></div>
<script>
// Taken from Chromium bug: http://crbug.com/822510
test(() => {
    const range = new Range();
    const div = document.getElementById('div');
    const s0 = document.getElementById('s0');
    const s1 = document.getElementById('s1');
    const s2 = document.getElementById('s2');

    // Range encloses s0
    range.setStart(div, 0);
    range.setEnd(div, 1);
    assert_true(range.intersectsNode(s0), '[s0] range.intersectsNode(s0)');
    assert_false(range.intersectsNode(s1), '[s0] range.intersectsNode(s1)');
    assert_false(range.intersectsNode(s2), '[s0] range.intersectsNode(s2)');

    // Range encloses s1
    range.setStart(div, 1);
    range.setEnd(div, 2);
    assert_false(range.intersectsNode(s0), '[s1] range.intersectsNode(s0)');
    assert_true(range.intersectsNode(s1), '[s1] range.intersectsNode(s1)');
    assert_false(range.intersectsNode(s2), '[s1] range.intersectsNode(s2)');

    // Range encloses s2
    range.setStart(div, 2);
    range.setEnd(div, 3);
    assert_false(range.intersectsNode(s0), '[s2] range.intersectsNode(s0)');
    assert_false(range.intersectsNode(s1), '[s2] range.intersectsNode(s1)');
    assert_true(range.intersectsNode(s2), '[s2] range.intersectsNode(s2)');
}, 'Range.intersectsNode() simple cases');
</script>
TEST_HTML;
    }
}
