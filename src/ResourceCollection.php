<?php
namespace KS\JsonApi;

class ResourceCollection extends ArrayCollection implements \JsonSerializable {
    public function offsetSet($offset, $value) {
        if (!($value instanceof Resource)) {
            $type = gettype($value);
            if ($type == 'object') $type = "Object (".get_class($value).")";
            throw new \InvalidArgumentException("All values passed to a JsonApi ResourceCollection must be JsonApi Resources. Value is of type `".$type."`");
        }
        parent::offsetSet($offset, $value);
    }

    public function jsonSerialize(bool $fullResource=true) {
        $data = [];
        foreach($this->elements as $e) $data[] = $e->jsonSerialize($fullResource);
        return $data;
    }
}

