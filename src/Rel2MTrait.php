<?php
namespace KS\JsonApi;

trait Rel2MTrait {
    protected function add2MRel($name, BaseResourceInterface $resource, $private=false) {
        if (!in_array($name, $this->initializedRelationships)) throw new UninitializedRelationshipException("You have not yet initialized the to-many relationship `$name`. You must initialize this relationship before using it.");
        if (!$this->has2MRel($name, $resource, $private)) {
            if ($private) $this->privateRelationships[$name]->getData()[] = $resource;
            else $this->relationships[$name]->getData()[] = $resource;
        }
        return $this;
    }
    protected function has2MRel($name, BaseResourceInterface $resource=null, $private=false) {
        if (!in_array($name, $this->initializedRelationships)) throw new UninitializedRelationshipException("You have not yet initialized the to-many relationship `$name`. You must initialize this relationship before using it.");
        try {
            $index = $this->indexOf2MRel($name, $resource);
            return $index !== false;
        } catch (CollectionUndefinedIndexException $e) {
            return false;
        }
    }
    protected function indexOf2MRel($name, BaseResourceInterface $resource=null, $private=false) {
        $check = $private ? $this->privateRelationships[$name] : $this->relationships[$name];
        if ($resource && $check->getData()) {
            foreach($check->getData() as $k => $test) {
                if ($test->getId() == $resource->getId() && $test->getResourceType() == $resource->getResourceType()) return $k;
            }
        }
        throw new CollectionUndefinedIndexException("Resource is not in collection");
    }
    protected function remove2MRel($name, BaseResourceInterface $resource, $private=false) {
        try {
            $index = $this->indexOf2MRel($name, $resource);
            if ($private) unset($this->privateRelationships[$name]->getData()[$index]);
            else unset($this->relationships[$name]->getData()[$index]);
        } catch (CollectionUndefinedIndexException $e) {
            // Nothing to do if not in collection
        }
        return;
    }
}

