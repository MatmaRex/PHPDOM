<?php

namespace Rowbot\DOM\Tests\dom\ranges;

use Exception;
use Generator;
use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\NotSupportedError;
use Rowbot\DOM\Exception\WrongDocumentError;
use Rowbot\DOM\Range;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\TestCase;

use function abs;
use function array_push;
use function array_search;
use function count;
use function floor;
use function in_array;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/ranges/Range-compareBoundaryPoints.html
 */
class RangeCompareBoundaryPointsTest extends TestCase
{
    use Common;

    private static $extraTests;

    /**
     * @dataProvider rangeProvider
     */
    public function testCompareBoundaryPoints(Range $range1, int $i, Range $range2, int $j): void
    {
        $hows = [
            Range::START_TO_START,
            Range::START_TO_END,
            Range::END_TO_END,
            Range::END_TO_START,
        ];

        if (in_array($i, self::$extraTests, true) && in_array($j, self::$extraTests, true)) {
            // TODO: Make some type of reusable utility function to do this work.
            array_push($hows, -1, 4, 5, NAN, -0, +INF, -INF);

            foreach ([65536, -65536, 65536 * 65536, 0.5, -0.5, -72.5] as $addend) {
                array_push($hows, -1 + $addend, 0 + $addend, 1 + $addend, 2 + $addend, 3 + $addend, 4 + $addend);
            }

            foreach ($hows as $how) {
                $hows[] = (string) $how;
            }

            array_push($hows, "6.5536e4", null, true, false, "", "quasit");
        }

        foreach ($hows as $how) {
            $this->assertNotNull($range1);
            $this->assertNotNull($range2);

            $convertedHow = $how + 0;

            if (
                $convertedHow === NAN
                || $convertedHow === 0
                || $convertedHow === INF
                || $convertedHow === -INF
            ) {
                $convertedHow = 0;
            } else {
                // "Let posInt be sign(number) * floor(abs(number))."
                $posInt = ($convertedHow < 0 ? -1 : 1) * floor(abs($convertedHow));

                // "Let int16bit be posInt modulo 2^16; that is, a finite
                // integer value k of Number type with positive sign and
                // less than 2^16 in magnitude such that the mathematical
                // difference of posInt and k is mathematically an integer
                // multiple of 2^16."
                //
                // "Return int16bit."
                $convertedHow = $posInt % 65536;

                if ($convertedHow < 0) {
                    $convertedHow += 65536;
                }
            }

            // Now to the actual algorithm.
            // "If how is not one of
            //   START_TO_START,
            //   START_TO_END,
            //   END_TO_END, and
            //   END_TO_START,
            // throw a "NotSupportedError" exception and terminate these
            // steps."
            if (
                $convertedHow !== Range::START_TO_START
                && $convertedHow !== Range::START_TO_END
                && $convertedHow !== Range::END_TO_END
                && $convertedHow !== Range::END_TO_START
            ) {
                $this->assertThrows(static function () use ($range1, $range2, $how): void {
                    $range1->compareBoundaryPoints($how, $range2);
                }, NotSupportedError::class);

                return;
            }

            if ($this->furthestAncestor($range1->startContainer) !== $this->furthestAncestor($range2->startContainer)) {
                $this->assertThrows(static function () use ($range1, $range2, $how): void {
                    $range1->compareBoundaryPoints($how, $range2);
                }, WrongDocumentError::class);

                return;
            }

            // "If how is:
            //   START_TO_START:
            //     Let this point be the context object's start.
            //     Let other point be sourceRange's start.
            //   START_TO_END:
            //     Let this point be the context object's end.
            //     Let other point be sourceRange's start.
            //   END_TO_END:
            //     Let this point be the context object's end.
            //     Let other point be sourceRange's end.
            //   END_TO_START:
            //     Let this point be the context object's start.
            //     Let other point be sourceRange's end."
            $thisPoint = $convertedHow === Range::START_TO_START || $convertedHow === Range::END_TO_START
                ? [$range1->startContainer, $range1->startOffset]
                : [$range1->endContainer, $range1->endOffset];
            $otherPoint = $convertedHow === Range::START_TO_START || $convertedHow === Range::START_TO_END
                ? [$range2->startContainer, $range2->startOffset]
                : [$range2->endContainer, $range2->endOffset];

            // "If the position of this point relative to other point is
            //   before
            //     Return −1.
            //   equal
            //     Return 0.
            //   after
            //     Return 1."
            $position = $this->getPosition($thisPoint[0], $thisPoint[1], $otherPoint[0], $otherPoint[1]);

            if ($position === "before") {
                $expected = -1;
            } elseif ($position === "equal") {
                $expected = 0;
            } elseif ($position === "after") {
                $expected = 1;
            }

            $this->assertSame($expected, $range1->compareBoundaryPoints($how, $range2));
        }
    }

    public function rangeProvider(): Generator
    {
        global $document, $testRangesShort, $testRanges;

        $document = $this->getDocument();
        self::setupRangeTests($document);

        $testRangesCached = [];
        $testRangesCached[] = $document->createRange();

        foreach ($testRangesShort as $endpoints) {
            try {
                $testRangesCached[] = $this->rangeFromEndpoints($this->eval($endpoints, $document));
            } catch (Exception $e) {
                $testRangesCached[] = null;
            }
        }

        $testRangesCachedClones = [];
        $testRangesCachedClones[] = $document->createRange();
        $testRangesCachedClones[0]->detach();

        foreach ($testRangesCached as $range) {
            if ($range !== null) {
                $testRangesCachedClones[] = $range->cloneRange();
            } else {
                $testRangesCachedClones[] = null;
            }
        }

        self::$extraTests = [
            0, // detached
            1 + array_search('[$paras[0]->firstChild, 2, $paras[0]->firstChild, 8]', $testRanges, true),
            1 + array_search('[$paras[0]->firstChild, 3, $paras[3], 1]', $testRanges, true),
            1 + array_search('[$testDiv, 0, $comment, 5]', $testRanges, true),
            1 + array_search('[$foreignDoc->documentElement, 0, $foreignDoc->documentElement, 1]', $testRanges, true),
        ];

        $length = count($testRangesCachedClones);

        foreach ($testRangesCached as $i => $range1) {
            foreach ($testRangesCachedClones as $j => $range2) {
                if ($j === $length) {
                    $range2 = $range1;
                }

                yield [$range1, $i, $range2, $j];
            }
        }
    }

    public function getDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<title>Range.compareBoundaryPoints() tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>

<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

var testRangesCached = [];
testRangesCached.push(document.createRange());
testRangesCached[0].detach();
for (var i = 0; i < testRangesShort.length; i++) {
    try {
    testRangesCached.push(rangeFromEndpoints(eval(testRangesShort[i])));
    } catch(e) {
    testRangesCached.push(null);
    }
}

var testRangesCachedClones = [];
testRangesCachedClones.push(document.createRange());
testRangesCachedClones[0].detach();
for (var i = 1; i < testRangesCached.length; i++) {
    if (testRangesCached[i]) {
    testRangesCachedClones.push(testRangesCached[i].cloneRange());
    } else {
    testRangesCachedClones.push(null);
    }
}

// We want to run a whole bunch of extra tests with invalid "how" values (not
// 0-3), but it's excessive to run them for every single pair of ranges --
// there are too many of them.  So just run them for a handful of the tests.
var extraTests = [0, // detached
    1 + testRanges.indexOf("[paras[0].firstChild, 2, paras[0].firstChild, 8]"),
    1 + testRanges.indexOf("[paras[0].firstChild, 3, paras[3], 1]"),
    1 + testRanges.indexOf("[testDiv, 0, comment, 5]"),
    1 + testRanges.indexOf("[foreignDoc.documentElement, 0, foreignDoc.documentElement, 1]")];

for (var i = 0; i < testRangesCached.length; i++) {
    var range1 = testRangesCached[i];
    var range1Desc = i + " " + (i == 0 ? "[detached]" : testRanges[i - 1]);
    for (var j = 0; j <= testRangesCachedClones.length; j++) {
    var range2;
    var range2Desc;
    if (j == testRangesCachedClones.length) {
        range2 = range1;
        range2Desc = "same as first range";
    } else {
        range2 = testRangesCachedClones[j];
        range2Desc = j + " " + (j == 0 ? "[detached]" : testRanges[j - 1]);
    }

    var hows = [Range.START_TO_START, Range.START_TO_END, Range.END_TO_END,
        Range.END_TO_START];
    if (extraTests.indexOf(i) != -1 && extraTests.indexOf(j) != -1) {
        // TODO: Make some type of reusable utility function to do this
        // work.
        hows.push(-1, 4, 5, NaN, -0, +Infinity, -Infinity);
        [65536, -65536, 65536*65536, 0.5, -0.5, -72.5].forEach(function(addend) {
        hows.push(-1 + addend, 0 + addend, 1 + addend,
            2 + addend, 3 + addend, 4 + addend);
        });
        hows.forEach(function(how) { hows.push(String(how)) });
        hows.push("6.5536e4", null, undefined, true, false, "", "quasit");
    }

    for (var k = 0; k < hows.length; k++) {
        var how = hows[k];
        test(function() {
        assert_not_equals(range1, null,
            "Creating context range threw an exception");
        assert_not_equals(range2, null,
            "Creating argument range threw an exception");

        // Convert how per WebIDL.  TODO: Make some type of reusable
        // utility function to do this work.
        // "Let number be the result of calling ToNumber on the input
        // argument."
        var convertedHow = Number(how);

        // "If number is NaN, +0, −0, +∞, or −∞, return +0."
        if (isNaN(convertedHow)
        || convertedHow == 0
        || convertedHow == Infinity
        || convertedHow == -Infinity) {
            convertedHow = 0;
        } else {
            // "Let posInt be sign(number) * floor(abs(number))."
            var posInt = (convertedHow < 0 ? -1 : 1) * Math.floor(Math.abs(convertedHow));

            // "Let int16bit be posInt modulo 2^16; that is, a finite
            // integer value k of Number type with positive sign and
            // less than 2^16 in magnitude such that the mathematical
            // difference of posInt and k is mathematically an integer
            // multiple of 2^16."
            //
            // "Return int16bit."
            convertedHow = posInt % 65536;
            if (convertedHow < 0) {
            convertedHow += 65536;
            }
        }

        // Now to the actual algorithm.
        // "If how is not one of
        //   START_TO_START,
        //   START_TO_END,
        //   END_TO_END, and
        //   END_TO_START,
        // throw a "NotSupportedError" exception and terminate these
        // steps."
        if (convertedHow != Range.START_TO_START
        && convertedHow != Range.START_TO_END
        && convertedHow != Range.END_TO_END
        && convertedHow != Range.END_TO_START) {
            assert_throws_dom("NOT_SUPPORTED_ERR", function() {
            range1.compareBoundaryPoints(how, range2);
            }, "NotSupportedError required if first parameter doesn't convert to 0-3 per WebIDL");
            return;
        }

        // "If context object's root is not the same as sourceRange's
        // root, throw a "WrongDocumentError" exception and terminate
        // these steps."
        if (furthestAncestor(range1.startContainer) != furthestAncestor(range2.startContainer)) {
            assert_throws_dom("WRONG_DOCUMENT_ERR", function() {
            range1.compareBoundaryPoints(how, range2);
            }, "WrongDocumentError required if the ranges don't share a root");
            return;
        }

        // "If how is:
        //   START_TO_START:
        //     Let this point be the context object's start.
        //     Let other point be sourceRange's start.
        //   START_TO_END:
        //     Let this point be the context object's end.
        //     Let other point be sourceRange's start.
        //   END_TO_END:
        //     Let this point be the context object's end.
        //     Let other point be sourceRange's end.
        //   END_TO_START:
        //     Let this point be the context object's start.
        //     Let other point be sourceRange's end."
        var thisPoint = convertedHow == Range.START_TO_START || convertedHow == Range.END_TO_START
            ? [range1.startContainer, range1.startOffset]
            : [range1.endContainer, range1.endOffset];
        var otherPoint = convertedHow == Range.START_TO_START || convertedHow == Range.START_TO_END
            ? [range2.startContainer, range2.startOffset]
            : [range2.endContainer, range2.endOffset];

        // "If the position of this point relative to other point is
        //   before
        //     Return −1.
        //   equal
        //     Return 0.
        //   after
        //     Return 1."
        var position = getPosition(thisPoint[0], thisPoint[1], otherPoint[0], otherPoint[1]);
        var expected;
        if (position == "before") {
            expected = -1;
        } else if (position == "equal") {
            expected = 0;
        } else if (position == "after") {
            expected = 1;
        }

        assert_equals(range1.compareBoundaryPoints(how, range2), expected,
            "Wrong return value");
        }, i + "," + j + "," + k + ": context range " + range1Desc + ", argument range " + range2Desc + ", how " + format_value(how));
    }
    }
}

testDiv.style.display = "none";
</script>
TEST_HTML;

        $p = new DOMParser();

        return $p->parseFromString($html, 'text/html');
    }
}
