<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Exception;
use Generator;
use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\IndexSizeError;
use Rowbot\DOM\Exception\InvalidNodeTypeError;
use Rowbot\DOM\Exception\WrongDocumentError;
use Rowbot\DOM\Node;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\TestCase;

use function pow;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-comparePoint.html
 */
class RangeComparePointTest extends TestCase
{
    use Common;

    /**
     * @dataProvider pointsProvider
     */
    public function testComparePoint(Node $node, int $offset, Range $range): void
    {
        // comparePoint is an unsigned long, so per WebIDL, we need to treat it as
        // though it wrapped to an unsigned 32-bit integer.
        $normalizedOffset = $offset % pow(2, 32);

        if ($normalizedOffset < 0) {
            $normalizedOffset += pow(2, 32);
        }

        $this->assertNotNull($range);

        $range = $range->cloneRange();

        // "If node's root is different from the context object's root,
        // throw a "WrongDocumentError" exception and terminate these
        // steps."
        if ($this->furthestAncestor($node) !== $this->furthestAncestor($range->startContainer)) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->comparePoint($node, $offset);
            }, WrongDocumentError::class);

            return;
        }

        // "If node is a doctype, throw an "InvalidNodeTypeError" exception
        // and terminate these steps."
        if ($node->nodeType === Node::DOCUMENT_TYPE_NODE) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->comparePoint($node, $offset);
            }, InvalidNodeTypeError::class);

            return;
        }

        // "If offset is greater than node's length, throw an
        // "IndexSizeError" exception and terminate these steps."
        if ($normalizedOffset > $node->getLength()) {
            $this->assertThrows(static function () use ($range, $node, $offset): void {
                $range->comparePoint($node, $offset);
            }, IndexSizeError::class);

            return;
        }

        // "If (node, offset) is before start, return −1 and terminate
        // these steps."
        if ($this->getPosition($node, $normalizedOffset, $range->startContainer, $range->startOffset) === 'before') {
            $this->assertSame(-1, $range->comparePoint($node, $offset));

            return;
        }

        // "If (node, offset) is after end, return 1 and terminate these
        // steps."
        if ($this->getPosition($node, $normalizedOffset, $range->endContainer, $range->endOffset) === 'after') {
            $this->assertSame(1, $range->comparePoint($node, $offset));

            return;
        }

        // "Return 0."
        $this->assertSame(0, $range->comparePoint($node, $offset));
    }

    public function pointsProvider(): Generator
    {
        global $testRanges, $testPoints;

        $document = self::loadDocument();
        self::setupRangeTests($document);

        foreach ($testPoints as $point) {
            $evaled = $this->eval($point, $document);

            foreach ($testRanges as $range) {
                try {
                    $range = $this->rangeFromEndpoints($this->eval($range, $document));
                } catch (Exception $e) {
                    $range = null;
                }

                yield [$evaled[0], $evaled[1], $range];
            }
        }
    }

    public static function loadDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<title>Range.comparePoint() tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

// Will be filled in on the first run for that range
var testRangesCached = [];

for (var i = 0; i < testPoints.length; i++) {
    var node = eval(testPoints[i])[0];
    var offset = eval(testPoints[i])[1];

    // comparePoint is an unsigned long, so per WebIDL, we need to treat it as
    // though it wrapped to an unsigned 32-bit integer.
    var normalizedOffset = offset % Math.pow(2, 32);
    if (normalizedOffset < 0) {
    normalizedOffset += Math.pow(2, 32);
    }

    for (var j = 0; j < testRanges.length; j++) {
    test(function() {
        if (testRangesCached[j] === undefined) {
        try {
            testRangesCached[j] = rangeFromEndpoints(eval(testRanges[i]));
        } catch(e) {
            testRangesCached[j] = null;
        }
        }
        assert_not_equals(testRangesCached[j], null,
        "Setting up the range failed");

        var range = testRangesCached[j].cloneRange();

        // "If node's root is different from the context object's root,
        // throw a "WrongDocumentError" exception and terminate these
        // steps."
        if (furthestAncestor(node) !== furthestAncestor(range.startContainer)) {
        assert_throws_dom("WRONG_DOCUMENT_ERR", function() {
            range.comparePoint(node, offset);
        }, "Must throw WrongDocumentError if node and range have different roots");
        return;
        }

        // "If node is a doctype, throw an "InvalidNodeTypeError" exception
        // and terminate these steps."
        if (node.nodeType == Node.DOCUMENT_TYPE_NODE) {
        assert_throws_dom("INVALID_NODE_TYPE_ERR", function() {
            range.comparePoint(node, offset);
        }, "Must throw InvalidNodeTypeError if node is a doctype");
        return;
        }

        // "If offset is greater than node's length, throw an
        // "IndexSizeError" exception and terminate these steps."
        if (normalizedOffset > nodeLength(node)) {
        assert_throws_dom("INDEX_SIZE_ERR", function() {
            range.comparePoint(node, offset);
        }, "Must throw IndexSizeError if offset is greater than  length");
        return;
        }

        // "If (node, offset) is before start, return −1 and terminate
        // these steps."
        if (getPosition(node, normalizedOffset, range.startContainer, range.startOffset) === "before") {
        assert_equals(range.comparePoint(node, offset), -1,
            "Must return -1 if point is before start");
        return;
        }

        // "If (node, offset) is after end, return 1 and terminate these
        // steps."
        if (getPosition(node, normalizedOffset, range.endContainer, range.endOffset) === "after") {
        assert_equals(range.comparePoint(node, offset), 1,
            "Must return 1 if point is after end");
        return;
        }

        // "Return 0."
        assert_equals(range.comparePoint(node, offset), 0,
        "Must return 0 if point is neither before start nor after end");
    }, "Point " + i + " " + testPoints[i] + ", range " + j + " " + testRanges[j]);
    }
}

testDiv.style.display = "none";
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }
}
