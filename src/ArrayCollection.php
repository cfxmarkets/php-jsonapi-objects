<?php
namespace KS\JsonApi;

class ArrayCollection implements \ArrayAccess, \Iterator, \Countable {
    use \KS\ArrayAccessTrait {
        offsetSet as protected parentOffsetSet;
    }
    use \KS\IteratorTrait;
    use \KS\CountableTrait;

    public function __construct(array $items=[]) {
        foreach($items as $k => $v) $this[$k] = $v;
    }

    public function offsetSet($offset, $value) {
        if ($offset !== null && !is_int($offset)) throw new \RuntimeException("A JsonApi ArrayCollection object cannot be string-indexed. All indexes must be integers. (Offending index: `$offset`.)");
        $this->parentOffsetSet($offset, $value);
    }
}

