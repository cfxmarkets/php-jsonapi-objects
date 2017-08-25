<?php
namespace KS\JsonApi;

class ErrorsCollection extends ArrayCollection {
    public function offsetSet($offset, $value) {
        if (!($value instanceof Error)) throw new \InvalidArgumentException("All values passed to a JsonApi ErrorsCollection must be JsonApi Errors. Value is of type `".get_class($value)."`");
        parent::offsetSet($offset, $value);
    }
}

