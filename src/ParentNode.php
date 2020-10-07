<?php
declare(strict_types=1);

namespace Rowbot\DOM;

use Rowbot\DOM\Element\Element;

use function count;

/**
 * @see https://dom.spec.whatwg.org/#interface-parentnode
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ParentNode
 */
trait ParentNode
{
    use ChildOrParentNode;

    /**
     * Inserts nodes after the last child of this node, while replacing strings
     * in nodes with equvilant Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-append
     *
     * @param Node|string ...$nodes One or more Nodes or strings to be appended to this Node.
     *
     * @return void
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function append(...$nodes): void
    {
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);
        $this->preinsertNode($node, null);
    }

    /**
     * Inserts nodes before the first child of this node, while replacing
     * strings in nodes with equivalent Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-prepend
     *
     * @param Node|string ...$nodes One or more Nodes or strings to be prepended to this node
     *
     * @return void
     *
     * @throws \Rowbot\DOM\Exception\HierarchyRequestError
     */
    public function prepend(...$nodes): void
    {
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);
        $this->preinsertNode($node, $this->childNodes->first());
    }

    /**
     * Replaces all children of node with nodes, while replacing strings in nodes with equivilant
     * Text nodes.
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-replacechildren
     *
     * @param \Rowbot\DOM\Node|string $nodes One or more Nodes or strings to replace
     */
    public function replaceChildren(...$nodes): void
    {
        // 1. Let node be the result of converting nodes into a node given nodes and this’s node
        // document.
        $node = $this->convertNodesToNode($nodes, $this->nodeDocument);

        // 2. Ensure pre-insertion validity of node into this before null.
        $this->ensurePreinsertionValidity($node, null);

        // 3. Replace all with node within this.
        $this->replaceAllNodes($node);
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-children
     *
     * @return \Rowbot\DOM\Element\Element[]
     */
    protected function getChildren(): array
    {
        return $this->childNodes->filter(function ($node) {
            return $node->nodeType == Node::ELEMENT_NODE;
        })->all();
    }

    /**
     * Gets the first element child.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-firstelementchild
     *
     * @return \Rowbot\DOM\Element\Element|null
     */
    protected function getFirstElementChild(): ?Element
    {
        $node = $this->childNodes->first();

        while ($node) {
            if ($node instanceof Element) {
                break;
            }

            $node = $node->nextSibling;
        }

        return $node;
    }

    /**
     * Gets the last element child.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-lastelementchild
     *
     * @return \Rowbot\DOM\Element\Element|null
     */
    protected function getLastElementChild(): ?Element
    {
        $node = $this->childNodes->last();

        while ($node) {
            if ($node instanceof Element) {
                break;
            }

            $node = $node->previousSibling;
        }

        return $node;
    }

    /**
     * Gets the number of element children.
     *
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#dom-parentnode-childelementcount
     *
     * @return int
     */
    protected function getChildElementCount(): int
    {
        return count($this->getChildren());
    }
}
