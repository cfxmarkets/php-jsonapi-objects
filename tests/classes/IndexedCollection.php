<?php
namespace Test;

class IndexedCollection extends \KS\JsonApi\IndexedCollection {
    public function summarize() {
        $str = [];
        foreach($this->elements as $e) $str[] = ($e->getMemberName() ?: '(unnamed)').": ".($e->getData() ?: '(no data)');
        return implode("; ", $str);
    }
}

