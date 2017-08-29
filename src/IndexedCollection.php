<?php
namespace KS\JsonApi;

abstract class IndexedCollection extends Collection implements IndexedCollectionInterface  {
    public function __construct($items=[]) {
        foreach($items as $k => $v) $this[$k] = $v;
    }

    public function offsetSet($offset, $value) {
        if (!($value instanceof NamedMemberInterface)) throw new \InvalidArgumentException("All members of IndexedCollections must implement the \KS\JsonApi\NamedMemberInterface");
        if ($value->getMemberName()) {
            foreach($this->elements as $e) {
                if ($e->getMemberName() == $value->getMemberName()) throw new CollectionConflictingMemberException("There is already a member in this collection with the same name as the one you're trying to add (`".$value->getMemberName()."`). You cannot add members with duplicate names to an indexed collection.");
            }
        }
        parent::offsetSet($offset, $value);
    }

    public function jsonSerialize($fullResource=true) {
        $data = [];
        foreach($this->elements as $k => $e) {
            if (!$e->getMemberName()) throw new UnserializableObjectStateException("Trying to serialize an indexed collection, but some members are unnamed at the time of serialization. (Unnamed member found at index `$k`.)");
            if (array_key_exists($e->getMemberName(), $data)) throw new CollectionConflictingMemberException("Trying to serialize a collection with conflicting members. There are at least two members named `".$e->getMemberName()."` in this collection.");
            $data[$e->getMemberName()] = $e->jsonSerialize($fullResource);
        }
        return $data;
    }
}


