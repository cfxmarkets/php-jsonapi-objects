<?php
namespace CFX\JsonApi;

class ResourceCollection extends Collection implements ResourceCollectionInterface {
    public function offsetSet($offset, $value) {
        if (!($value instanceof ResourceInterface)) {
            $type = gettype($value);
            if ($type == 'object') $type = "Object (".get_class($value).")";
            throw new \InvalidArgumentException("All values passed to a JsonApi ResourceCollection must be JsonApi Resources. Value is of type `".$type."`");
        }
        parent::offsetSet($offset, $value);
    }

    public function summarize() {
        $str = [];
        foreach($this->elements as $r) {
            if ($r->getId()) {
                $id = $r->getId();
            } else {
                $id = "initial-".rand(1,10000);
            }
            $str[$id] = "{$r->getResourceType()}(#$id)";
        }
        if (count($str)) {
            ksort($str);
            return '['.implode("; ", $str).']';
        } else {
            return "[]";
        }
    }
}

