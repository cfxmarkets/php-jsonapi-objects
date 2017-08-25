<?php
namespace KS\JsonApi;

class IndexedCollection implements \ArrayAccess, \Iterator, \Countable {
    use \KS\ArrayAccessTrait;
    use \KS\IteratorTrait;
    use \KS\CountableTrait;

    public function __construct(array $items=[]) {
        foreach($items as $k => $v) $this[$k] = $v;
    }
}

