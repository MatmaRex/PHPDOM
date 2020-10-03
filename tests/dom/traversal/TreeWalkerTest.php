<?php

namespace Rowbot\DOM\Tests\dom\traversal;

use Closure;
use Rowbot\DOM\Document;
use Rowbot\DOM\DOMParser;
use Rowbot\DOM\Exception\InvalidStateError;
use Rowbot\DOM\Node;
use Rowbot\DOM\NodeFilter;
use Rowbot\DOM\Tests\dom\Common;
use Rowbot\DOM\Tests\dom\Window;
use Rowbot\DOM\TreeWalker;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/dom/traversal/TreeWalker.html
 */
class TreeWalkerTest extends TestCase
{
    use Common;

    private static $window;

    public function testRecursiveFilterNeedToThrow(): void
    {
        $document = self::getWindow()->document;
        $depth = 0;
        $walker = $document->createTreeWalker(
            $document,
            NodeFilter::SHOW_ALL,
            static function () use (&$depth, &$walker): int {
                if ($depth === 0) {
                    ++$depth;
                    $walker->firstChild();
                }

                return NodeFilter::FILTER_ACCEPT;
            }
        );
        $walker->currentNode = $document->body;

        $this->assertThrows(static function () use ($walker): void {
            $walker->parentNode();
        }, InvalidStateError::class);
        --$depth;
        $this->assertThrows(static function () use ($walker): void {
            $walker->firstChild();
        }, InvalidStateError::class);
        --$depth;
        $this->assertThrows(static function () use ($walker): void {
            $walker->lastChild();
        }, InvalidStateError::class);
        --$depth;
        $this->assertThrows(static function () use ($walker): void {
            $walker->previousSibling();
        }, InvalidStateError::class);
        --$depth;
        $this->assertThrows(static function () use ($walker): void {
            $walker->nextSibling();
        }, InvalidStateError::class);
        --$depth;
        $this->assertThrows(static function () use ($walker): void {
            $walker->previousNode();
        }, InvalidStateError::class);
        --$depth;
        $this->assertThrows(static function () use ($walker): void {
            $walker->nextNode();
        }, InvalidStateError::class);
    }

    /**
     * @dataProvider treeWalkerInputProvider
     */
    public function testWalker(Node $root, int $whatToShow, ?Closure $filter): void
    {
        $walker = self::getWindow()->document->createTreeWalker($root, $whatToShow, $filter);

        $this->assertSame($root, $walker->root);
        $this->assertSame($whatToShow, $walker->whatToShow);
        $this->assertSameFilter($walker, $filter);
        $this->assertSame($root, $walker->currentNode);

        $expectedReturn = null;
        $expectedCurrentNode = $walker->currentNode;

        // "The parentNode() method must run these steps:"
        //
        // "Let node be the value of the currentNode attribute."
        $node = $walker->currentNode;

        // "While node is not null and is not root, run these substeps:"
        while ($node && $node !== $root) {
            // "Let node be node's parent."
            $node = $node->parentNode;

            if (
                $node
                && $this->filterNode($node, $whatToShow, $filter) === NodeFilter::FILTER_ACCEPT
            ) {
                $expectedCurrentNode = $expectedReturn = $node;
            }
        }

        $this->assertSame($expectedReturn, $walker->parentNode());
        $this->assertSame($expectedCurrentNode, $walker->currentNode);

        $this->_testTraverseChildren('first', $walker, $root, $whatToShow, $filter);
        $this->_testTraverseChildren('last', $walker, $root, $whatToShow, $filter);

        $this->_testTraverseSiblings('next', $walker, $root, $whatToShow, $filter);
        $this->_testTraverseSiblings('previous', $walker, $root, $whatToShow, $filter);
    }

    public function _testTraverseChildren(
        string $type,
        TreeWalker $walker,
        Node $root,
        int $whatToShow,
        ?Closure $filter
    ): void {
        // TODO We don't test .currentNode other than the root
        $walker->currentNode = $root;

        $this->assertSame($root, $walker->currentNode);

        $expectedReturn = null;
        $expectedCurrentNode = $root;

        // "To traverse children of type type, run these steps:
        //
        // "Let node be the value of the currentNode attribute."
        $node = $walker->currentNode;

        // "Set node to node's first child if type is first, and node's last child
        // if type is last."
        $node = $type === 'first' ? $node->firstChild : $node->lastChild;

        // "Main: While node is not null, run these substeps:"
        while ($node) {
            // "Filter node and let result be the return value."
            $result = $this->filterNode($node, $whatToShow, $filter);

            // "If result is FILTER_ACCEPT, then set the currentNode attribute to
            // node and return node."
            if ($result === NodeFilter::FILTER_ACCEPT) {
                $expectedCurrentNode = $expectedReturn = $node;

                break;
            }

            // "If result is FILTER_SKIP, run these subsubsteps:"
            if ($result === NodeFilter::FILTER_SKIP) {
                // "Let child be node's first child if type is first, and node's
                // last child if type is last."
                $child = $type === 'first' ? $node->firstChild : $node->lastChild;

                // "If child is not null, set node to child and goto Main."
                if ($child) {
                    $node = $child;

                    continue;
                }
            }

            // "While node is not null, run these subsubsteps:"
            while ($node) {
                // "Let sibling be node's next sibling if type is first, and node's
                // previous sibling if type is last."
                $sibling = $type === 'first' ? $node->nextSibling : $node->previousSibling;

                // "If sibling is not null, set node to sibling and goto Main."
                if ($sibling) {
                    $node = $sibling;

                    break;
                }

                // "Let parent be node's parent."
                $parent = $node->parentNode;

                // "If parent is null, parent is root, or parent is currentNode
                // attribute's value, return null."
                if (!$parent || $parent === $root || $parent === $walker->currentNode) {
                    $expectedReturn = $node = null;

                    break;
                } else {
                    // "Otherwise, set node to parent."
                    $node = $parent;
                }
            }
        }

        if ($type === 'first') {
            $this->assertSame($expectedReturn, $walker->firstChild());
            $this->assertSame($expectedCurrentNode, $walker->currentNode);
        } else {
            $this->assertSame($expectedReturn, $walker->lastChild());
            $this->assertSame($expectedCurrentNode, $walker->currentNode);
        }
    }

    public function _testTraverseSiblings(
        string $type,
        TreeWalker $walker,
        Node $root,
        int $whatToShow,
        ?Closure $filter
    ): void {
        // TODO We don't test .currentNode other than the root's first or last child
        if (!$root->firstChild) {
            // Nothing much to test
            $walker->currentNode = $root;

            $this->assertSame($root, $walker->currentNode);

            if ($type === 'next') {
                $this->assertNull($walker->nextSibling());
                $this->assertSame($root, $walker->currentNode);
            } else {
                $this->assertNull($walker->previousSibling());
                $this->assertSame($root, $walker->currentNode);
            }

            return;
        }

        if ($type === 'next') {
            $walker->currentNode = $root->firstChild;
            $this->assertSame($root->firstChild, $walker->currentNode);
        } else {
            $walker->currentNode = $root->lastChild;
            $this->assertSame($root->lastChild, $walker->currentNode);
        }

        $expectedReturn = null;
        $expectedCurrentNode = $type === 'next' ? $root->firstChild : $root->lastChild;

        // "To traverse siblings of type type run these steps:"
        $node = $type === 'next' ? $root->firstChild : $root->lastChild;

        // "If node is root, return null.
        //
        // "Run these substeps:
        do {
            // "Let sibling be node's next sibling if type is next, and node's
            // previous sibling if type is previous."
            $sibling = $type === 'next' ? $node->nextSibling : $node->previousSibling;

            // "While sibling is not null, run these subsubsteps:"
            while ($sibling) {
                // "Set node to sibling."
                $node = $sibling;

                // "Filter node and let result be the return value."
                $result = $this->filterNode($node, $whatToShow, $filter);

                // "If result is FILTER_ACCEPT, then set the currentNode
                // attribute to node and return node."
                if ($result === NodeFilter::FILTER_ACCEPT) {
                    $expectedCurrentNode = $expectedReturn = $node;
                    return;
                }

                // "Set sibling to node's first child if type is next, and
                // node's last child if type is previous."
                $sibling = $type === "next" ? $node->firstChild : $node->lastChild;

                // "If result is FILTER_REJECT or sibling is null, then set
                // sibling to node's next sibling if type is next, and node's
                // previous sibling if type is previous."
                if ($result === NodeFilter::FILTER_REJECT || !$sibling) {
                    $sibling = $type === "next" ? $node->nextSibling : $node->previousSibling;
                }
            }

            // "Set node to its parent."
            $node = $node->parentNode;

            // "If node is null or is root, return null.
            if (!$node || $node === $root) {
                return;
            }

            // "Filter node and if the return value is FILTER_ACCEPT, then
            // return null."
            if ($this->filterNode($node, $whatToShow, $filter) === NodeFilter::FILTER_ACCEPT) {
                return;
            }

            // "Run these substeps again."
        } while (true);

        if ($type === 'next') {
            $this->assertSame($expectedReturn, $walker->nextSibling());
            $this->assertSame($expectedCurrentNode, $walker->currentNode);
        } else {
            $this->assertSame($expectedReturn, $walker->previousSibling());
            $this->assertSame($expectedCurrentNode, $walker->currentNode);
        }
    }

    public function filterNode(Node $node, int $whatToShow, ?Closure $filter): int
    {
        // "If active flag is set throw an "InvalidStateError"."
        // Ignore active flag for these tests, we aren't calling recursively
        // TODO Test me

        // "Let n be node's nodeType attribute value minus 1."
        $n = $node->nodeType - 1;

        // "If the nth bit (where 0 is the least significant bit) of whatToShow is
        // not set, return FILTER_SKIP."
        if (!($whatToShow & (1 << $n))) {
            return NodeFilter::FILTER_SKIP;
        }

        // "If filter is null, return FILTER_ACCEPT."
        if (!$filter) {
            return NodeFilter::FILTER_ACCEPT;
        }

        // "Set the active flag."
        //
        // "Let result be the return value of invoking filter."
        //
        // "Unset the active flag."
        //
        // "If an exception was thrown, re-throw the exception."
        // TODO Test me
        //
        // "Return result."
        return $filter($node);
    }

    public function treeWalkerInputProvider(): array
    {
        $window = self::getWindow();
        $window->setupRangeTests();
        $whatToShows = [
            0,
            0xFFFFFFFF,
            NodeFilter::SHOW_ELEMENT,
            NodeFilter::SHOW_ATTRIBUTE,
            NodeFilter::SHOW_ELEMENT | NodeFilter::SHOW_DOCUMENT,
        ];
        $callbacks = [
            null,
            static function (Node $node): int {
                //return true;
                return NodeFilter::FILTER_ACCEPT;
            },
            static function (Node $node): int {
                //return false;
                return NodeFilter::FILTER_REJECT;
            },
            static function (Node $node): int {
                //return $node->nodeName[0] === '#';
                return $node->nodeName[0] === '#'
                    ? NodeFilter::FILTER_ACCEPT
                    : NodeFilter::FILTER_REJECT;
            },
        ];
        $tests = [];

        foreach ($window->testNodes as $testNode) {
            foreach ($whatToShows as $whatToShow) {
                foreach ($callbacks as $callback) {
                    $tests[] = [$window->eval($testNode), $whatToShow, $callback];
                }
            }
        }

        return $tests;
    }

    public static function getWindow(): Window
    {
        if (self::$window) {
            return self::$window;
        }

        self::$window = new Window(self::loadDocument());

        return self::$window;
    }

    public static function loadDocument(): Document
    {
        $html = <<<'TEST_HTML'
<!doctype html>
<title>TreeWalker tests</title>
<link rel="author" title="Aryeh Gregor" href=ayg@aryeh.name>
<meta name=timeout content=long>
<div id=log></div>
<script src=/resources/testharness.js></script>
<script src=/resources/testharnessreport.js></script>
<script src=../common.js></script>
<script>
"use strict";

// TODO .previousNode, .nextNode

test(function() {
    var depth = 0;
    var walker = document.createTreeWalker(document, NodeFilter.SHOW_ALL,
    function() {
        if (depth == 0) {
        depth++;
        walker.firstChild();
        }
        return NodeFilter.FILTER_ACCEPT;
    });
    walker.currentNode = document.body;
    assert_throws_dom("InvalidStateError", function() { walker.parentNode() });
    depth--;
    assert_throws_dom("InvalidStateError", function() { walker.firstChild() });
    depth--;
    assert_throws_dom("InvalidStateError", function() { walker.lastChild() });
    depth--;
    assert_throws_dom("InvalidStateError", function() { walker.previousSibling() });
    depth--;
    assert_throws_dom("InvalidStateError", function() { walker.nextSibling() });
    depth--;
    assert_throws_dom("InvalidStateError", function() { walker.previousNode() });
    depth--;
    assert_throws_dom("InvalidStateError", function() { walker.nextNode() });
}, "Recursive filters need to throw");

function filterNode(node, whatToShow, filter) {
    // "If active flag is set throw an "InvalidStateError"."
    // Ignore active flag for these tests, we aren't calling recursively
    // TODO Test me

    // "Let n be node's nodeType attribute value minus 1."
    var n = node.nodeType - 1;

    // "If the nth bit (where 0 is the least significant bit) of whatToShow is
    // not set, return FILTER_SKIP."
    if (!(whatToShow & (1 << n))) {
        return NodeFilter.FILTER_SKIP;
    }

    // "If filter is null, return FILTER_ACCEPT."
    if (!filter) {
        return NodeFilter.FILTER_ACCEPT;
    }

    // "Set the active flag."
    //
    // "Let result be the return value of invoking filter."
    //
    // "Unset the active flag."
    //
    // "If an exception was thrown, re-throw the exception."
    // TODO Test me
    //
    // "Return result."
    return filter(node);
}

function testTraverseChildren(type, walker, root, whatToShow, filter) {
    // TODO We don't test .currentNode other than the root
    walker.currentNode = root;
    assert_equals(walker.currentNode, root, "Setting .currentNode");

    var expectedReturn = null;
    var expectedCurrentNode = root;

    // "To traverse children of type type, run these steps:
    //
    // "Let node be the value of the currentNode attribute."
    var node = walker.currentNode;

    // "Set node to node's first child if type is first, and node's last child
    // if type is last."
    node = type == "first" ? node.firstChild : node.lastChild;

    // "Main: While node is not null, run these substeps:"
    while (node) {
        // "Filter node and let result be the return value."
        var result = filterNode(node, whatToShow, filter);

        // "If result is FILTER_ACCEPT, then set the currentNode attribute to
        // node and return node."
        if (result == NodeFilter.FILTER_ACCEPT) {
            expectedCurrentNode = expectedReturn = node;
            break;
        }

        // "If result is FILTER_SKIP, run these subsubsteps:"
        if (result == NodeFilter.FILTER_SKIP) {
            // "Let child be node's first child if type is first, and node's
            // last child if type is last."
            var child = type == "first" ? node.firstChild : node.lastChild;

            // "If child is not null, set node to child and goto Main."
            if (child) {
                node = child;
                continue;
            }
        }

        // "While node is not null, run these subsubsteps:"
        while (node) {
            // "Let sibling be node's next sibling if type is first, and node's
            // previous sibling if type is last."
            var sibling = type == "first" ? node.nextSibling
                : node.previousSibling;

            // "If sibling is not null, set node to sibling and goto Main."
            if (sibling) {
                node = sibling;
                break;
            }

            // "Let parent be node's parent."
            var parent = node.parentNode;

            // "If parent is null, parent is root, or parent is currentNode
            // attribute's value, return null."
            if (!parent || parent == root || parent == walker.currentNode) {
                expectedReturn = node = null;
                break;
            } else {
            // "Otherwise, set node to parent."
                node = parent;
            }
        }
    }

    if (type == "first") {
        assert_equals(walker.firstChild(), expectedReturn, ".firstChild()");
        assert_equals(walker.currentNode, expectedCurrentNode,
            ".currentNode after .firstChild()");
    } else {
        assert_equals(walker.lastChild(), expectedReturn, ".lastChild()");
        assert_equals(walker.currentNode, expectedCurrentNode,
        ".currentNode after .lastChild()");
    }
}

function testTraverseSiblings(type, walker, root, whatToShow, filter) {
    // TODO We don't test .currentNode other than the root's first or last child
    if (!root.firstChild) {
        // Nothing much to test

        walker.currentNode = root;
        assert_equals(walker.currentNode, root, "Setting .currentNode");

        if (type == "next") {
            assert_equals(walker.nextSibling(), null, ".nextSibling()");
            assert_equals(walker.currentNode, root,
                ".currentNode after .nextSibling()")
        } else {
            assert_equals(walker.previousSibling(), null, ".previousSibling()");
            assert_equals(walker.currentNode, root,
                ".currentNode after .previousSibling()")
        }
        return;
    }

    if (type == "next") {
        walker.currentNode = root.firstChild;
        assert_equals(walker.currentNode, root.firstChild,
            "Setting .currentNode");
    } else {
        walker.currentNode = root.lastChild;
        assert_equals(walker.currentNode, root.lastChild,
            "Setting .currentNode");
    }

    var expectedReturn = null;
    var expectedCurrentNode = type == "next" ? root.firstChild : root.lastChild;

    // "To traverse siblings of type type run these steps:"
    (function() {
        // "Let node be the value of the currentNode attribute."
        var node = type == "next" ? root.firstChild : root.lastChild;

        // "If node is root, return null.
        //
        // "Run these substeps:
        do {
            // "Let sibling be node's next sibling if type is next, and node's
            // previous sibling if type is previous."
            var sibling = type == "next" ? node.nextSibling :
                node.previousSibling;

            // "While sibling is not null, run these subsubsteps:"
            while (sibling) {
                // "Set node to sibling."
                node = sibling;

                // "Filter node and let result be the return value."
                var result = filterNode(node, whatToShow, filter);

                // "If result is FILTER_ACCEPT, then set the currentNode
                // attribute to node and return node."
                if (result == NodeFilter.FILTER_ACCEPT) {
                    expectedCurrentNode = expectedReturn = node;
                    return;
                }

                // "Set sibling to node's first child if type is next, and
                // node's last child if type is previous."
                sibling = type == "next" ? node.firstChild : node.lastChild;

                // "If result is FILTER_REJECT or sibling is null, then set
                // sibling to node's next sibling if type is next, and node's
                // previous sibling if type is previous."
                if (result == NodeFilter.FILTER_REJECT || !sibling) {
                    sibling = type == "next" ? node.nextSibling :
                        node.previousSibling;
                }
            }

            // "Set node to its parent."
            node = node.parentNode;

            // "If node is null or is root, return null.
            if (!node || node == root) {
                return;
            }
            // "Filter node and if the return value is FILTER_ACCEPT, then
            // return null."
            if (filterNode(node, whatToShow, filter)) {
                return;
            }

            // "Run these substeps again."
        } while (true);
    })();

    if (type == "next") {
        assert_equals(walker.nextSibling(), expectedReturn, ".nextSibling()");
        assert_equals(walker.currentNode, expectedCurrentNode,
            ".currentNode after .nextSibling()");
    } else {
        assert_equals(walker.previousSibling(), expectedReturn, ".previousSibling()");
        assert_equals(walker.currentNode, expectedCurrentNode,
            ".currentNode after .previousSibling()");
    }
}

function testWalker(root, whatToShow, filter) {
    var walker = document.createTreeWalker(root, whatToShow, filter);

    assert_equals(walker.root, root, ".root");
    assert_equals(walker.whatToShow, whatToShow, ".whatToShow");
    assert_equals(walker.filter, filter, ".filter");
    assert_equals(walker.currentNode, root, ".currentNode");

    var expectedReturn = null;
    var expectedCurrentNode = walker.currentNode;
    // "The parentNode() method must run these steps:"
    //
    // "Let node be the value of the currentNode attribute."
    var node = walker.currentNode;

    // "While node is not null and is not root, run these substeps:"
    while (node && node != root) {
        // "Let node be node's parent."
        node = node.parentNode;

        // "If node is not null and filtering node returns FILTER_ACCEPT, then
        // set the currentNode attribute to node, return node."
        if (node && filterNode(node, whatToShow, filter) ==
        NodeFilter.FILTER_ACCEPT) {
            expectedCurrentNode = expectedReturn = node;
        }
    }
    assert_equals(walker.parentNode(), expectedReturn, ".parentNode()");
    assert_equals(walker.currentNode, expectedCurrentNode,
        ".currentNode after .parentNode()");

    testTraverseChildren("first", walker, root, whatToShow, filter);
    testTraverseChildren("last", walker, root, whatToShow, filter);

    testTraverseSiblings("next", walker, root, whatToShow, filter);
    testTraverseSiblings("previous", walker, root, whatToShow, filter);
}

var whatToShows = [
    "0",
    "0xFFFFFFFF",
    "NodeFilter.SHOW_ELEMENT",
    "NodeFilter.SHOW_ATTRIBUTE",
    "NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_DOCUMENT",
];

var callbacks = [
    "null",
    "(function(node) { return true })",
    "(function(node) { return false })",
    "(function(node) { return node.nodeName[0] == '#' })",
];

var tests = [];
for (var i = 0; i < testNodes.length; i++) {
    for (var j = 0; j < whatToShows.length; j++) {
        for (var k = 0; k < callbacks.length; k++) {
            tests.push([
                "document.createTreeWalker(" + testNodes[i] +
                    ", " + whatToShows[j] + ", " + callbacks[k] + ")",
                eval(testNodes[i]), eval(whatToShows[j]), eval(callbacks[k])
            ]);
        }
    }
}
generate_tests(testWalker, tests);

testDiv.style.display = "none";
</script>
TEST_HTML;

        $parser = new DOMParser();

        return $parser->parseFromString($html, 'text/html');
    }

    public static function tearDownAfterClass(): void
    {
        self::$window = null;
    }
}
