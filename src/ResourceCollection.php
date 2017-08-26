<?php
namespace KS\JsonApi;

class ResourceCollection extends Collection {
    public function offsetSet($offset, $value) {
        if (!($value instanceof Resource)) {
            $type = gettype($value);
            if ($type == 'object') $type = "Object (".get_class($value).")";
            throw new \InvalidArgumentException("All values passed to a JsonApi ResourceCollection must be JsonApi Resources. Value is of type `".$type."`");
        }
        parent::offsetSet($offset, $value);
    }

    public function summarize() {
        $str = [];
        foreach($this->elements as $r) {
            $type = ucfirst($r->getType());
            $str[] = "$type resource #{$resource->getId()}";
        }
        return implode("; ", $str);
    }
}

