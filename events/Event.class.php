<?php
namespace phpjs\events;

use phpjs\Utils;

/**
 * Represents an event which can be dispatched to different objects to signal
 * the occurance of an event.
 *
 * @link https://dom.spec.whatwg.org/#event
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Event
 *
 * @property-read bool $bubbles Returns true if the event will traverse through
 *     its ancestors in reverse tree order, otherwise false.
 *
 * @property-read bool $cancelable Returns true if the event's default action
 *     can be prevented, otherwise false.
 *
 * @property-read Node|object $currentTarget Returns the current object or Node
 *     whose event listeners are currently being invoked.
 *
 * @property-read bool $defaultPrevented Returns true if the event's
 *     preventDefault() method is invoked and the event's cancelable attribute
 *     is true, otherwise false.
 *
 * @property-read int $eventPhase Returns the current phase that the event is
 *     in.  One of the following possibilities:
 *
 *         - NONE: Events that are not currently being dispatched.
 *         - CAPTURING_PHASE: Events that are currently invoking event listeners
 *             in tree order.
 *         - AT_TARGET: Events that are currently invoking event listeners on
 *             the target Node or object.
 *         - BUBBLING_PHASE: Events that are currently invoking event listeners
 *             in reverse tree order, assuming that the event's bubbling
 *             property is true.
 *
 * @property-read bool $isTrusted Returns true if the event was dispatched by
 *     the browser, otherwise false.
 *
 * @property-read Node|object $target Returns the Node or object that dispatched
 *     the event.
 *
 * @property-read int $timeStamp Returns the creation time of the even in
 *     milliseconds.
 *
 * @property-read string $type Returns the type of event that was created.
 */
class Event
{
    const NONE = 0;
    const CAPTURING_PHASE = 1;
    const AT_TARGET = 2;
    const BUBBLING_PHASE = 3;

    protected $mBubbles;
    protected $mCancelable;
    protected $mCurrentTarget;
    protected $mEventPhase;
    protected $mFlags;
    protected $mIsTrusted;
    protected $mPath;
    protected $mTarget;
    protected $mTimeStamp;
    protected $mType;

    public function __construct($aType, EventInit $aEventInitDict = null)
    {
        $initDict = $aEventInitDict ?: new EventInit();
        $this->mBubbles = $initDict->bubbles;
        $this->mCancelable =  $initDict->cancelable;
        $this->mCurrentTarget = null;
        $this->mEventPhase = self::NONE;
        $this->mFlags |= EventFlags::INITIALIZED;
        $this->mIsTrusted = false;
        $this->mPath = new \SplDoublyLinkedList();
        $this->mTarget = null;
        $this->mTimeStamp = microtime();
        $this->mType = $aType;
    }

    public function __destruct()
    {
        $this->mCurrentTarget = null;
        $this->mTarget = null;
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'bubbles':
                return $this->mBubbles;
            case 'cancelable':
                return $this->mCancelable;
            case 'currentTarget':
                return $this->mCurrentTarget;
            case 'defaultPrevented':
                return (bool) ($this->mFlags & EventFlags::CANCELED);
            case 'eventPhase':
                return $this->mEventPhase;
            case 'isTrusted':
                return $this->mIsTrusted;
            case 'target':
                return $this->mTarget;
            case 'timeStamp':
                return $this->mTimeStamp;
            case 'type':
                return $this->mType;
        }
    }

    /**
     * Initializes or reinitializes an event.
     *
     * @see https://dom.spec.whatwg.org/#dom-event-initevent
     *
     * @param string $aType The type of event to be created.
     *
     * @param boolean $aBubbles Optional. Whether or not the event will bubble
     *     up the tree, if the event is dispatched on an object that
     *     participates in a tree.  Defaults to false.
     *
     * @param boolean $aCancelable Optional. Whether or not the event's default
     *     action can be prevented.  Defaults to false.
     */
    public function initEvent($aType, $aBubbles = false, $aCancelable = false)
    {
        if ($this->mFlags & EventFlags::DISPATCH) {
            return;
        }

        $this->init(Utils::DOMString($aType), $aBubbles, $aCancelable);
    }

    /**
     * If the even'ts cancelable property is true, it signals that the operation
     * that caused the event needs to be canceled.
     *
     * @see https://dom.spec.whatwg.org/#dom-event-preventdefault
     */
    public function preventDefault()
    {
        if ($this->mCancelable &&
            !($this->mFlags & EventFlags::IN_PASSIVE_LISTENER)
        ) {
            $this->mFlags |= EventFlags::CANCELED;
        }
    }

    /**
     * If the event's target participates in a tree, this method will prevent
     * the event from reaching any objects that follow the current object.
     */
    public function stopPropagation()
    {
        $this->mFlags |= EventFlags::STOP_PROPAGATION;
    }

    /**
     * If the event's target participates in a tree, this method will prevent
     * the event from reaching any objects that follow the current object as
     * well as preventing any following event listeners from being invoked.
     */
    public function stopImmediatePropagation()
    {
        $this->mFlags |= EventFlags::STOP_PROPAGATION |
            EventFlags::STOP_IMMEDIATE_PROPAGATION;
    }

    /**
     * Gets the flags set for the Event object.
     *
     * @internal
     *
     * @return int
     */
    public function getFlags()
    {
        return $this->mFlags;
    }

    /**
     * Sets the Event object's current target.
     *
     * @internal
     *
     * @param mixed $aTarget The current event target.
     */
    public function setCurrentTarget($aTarget)
    {
        $this->mCurrentTarget = $aTarget;
    }

    /**
     * Sets the Event object's event phase.
     *
     * @internal
     *
     * @param int $aPhase An integer representing the current event phase.
     */
    public function setEventPhase($aPhase)
    {
        $this->mEventPhase = $aPhase;
    }

    /**
     * Sets a bitwise flag.
     *
     * @internal
     *
     * @param int $aFlag A bitwise flag.
     */
    public function setFlag($aFlag)
    {
        $this->mFlags |= $aFlag;
    }

    /**
     * Gets the Event object's path.
     *
     * @internal
     *
     * @return SPLDoublyLinkedList
     */
    public function getPath()
    {
        return $this->mPath;
    }

    /**
     * Empties the Event object's path by creating a new one.
     *
     * @internal
     */
    public function emptyPath()
    {
        $this->mPath = new \SplDoublyLinkedList();
    }

    /**
     * Sets the Event object's trusted state.
     *
     * @internal
     *
     * @param bool $aIsTrusted The trusted state of the event.
     */
    public function setIsTrusted($aIsTrusted)
    {
        $this->mIsTrusted = $aIsTrusted;
    }

    /**
     * Sets the Event object's target.
     *
     * @internal
     *
     * @param mixed $aTarget The event's target.
     */
    public function setTarget($aTarget)
    {
        $this->mTarget = $aTarget;
    }

    /**
     * Unsets a bitwise flag.
     *
     * @internal
     *
     * @param int $aFlag A bitwise flag.
     */
    public function unsetFlag($aFlag)
    {
        $this->mFlags &= ~$aFlag;
    }

    /**
     * @internal
     *
     * @see https://dom.spec.whatwg.org/#concept-event-initialize
     *
     * @param string $aType The event type.
     *
     * @param bool $aBubbles Whether the event bubbles or not.
     *
     * @param bool $aCancelable Whether the event is cancelable or not.
     */
    protected function init($aType, $aBubbles, $aCancelable)
    {
        $this->mFlags |= EventFlags::INTIALIZED;
        $this->mFlags &= ~(EventFlags::STOP_PROPAGATION |
            EventFlags::STOP_IMMEDIATE_PROPAGATION |
            EventFlags::CANCELED);
        $this->mIsTrusted = false;
        $this->mTarget = null;
        $this->mType = $aType;
        $this->mBubbles = $aBubbles;
        $this->mCancelable = $aCancelable;
    }
}
