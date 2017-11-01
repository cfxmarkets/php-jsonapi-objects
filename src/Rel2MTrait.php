<?php
namespace CFX\JsonApi;

trait Rel2MTrait {
    protected function get2MRel($name) {
        if (!in_array($name, $this->initializedRelationships)) $this->initialize2MRel($name);
        return $this->relationships[$name]->getData();
    }
    protected function add2MRel($name, ResourceInterface $resource) {
        if (!in_array($name, $this->initializedRelationships)) $this->initialize2MRel($name);
        if (!$this->has2MRel($name, $resource)) {
            $collection = $this->relationships[$name]->getData();
            $collection[] = $resource;
            $this->_setRelationship($name, $collection);
        }
        return $this;
    }
    protected function has2MRel($name, ResourceInterface $resource=null) {
        if (!in_array($name, $this->initializedRelationships)) $this->initialize2MRel($name);
        try {
            $index = $this->indexOf2MRel($name, $resource);
            return true;
        } catch (CollectionUndefinedIndexException $e) {
            return false;
        }
    }
    protected function indexOf2MRel($name, ResourceInterface $resource=null) {
        if (!in_array($name, $this->initializedRelationships)) $this->initialize2MRel($name);
        if ($resource && $this->relationships[$name]->getData()) {
            foreach($this->relationships[$name]->getData() as $k => $test) {
                if ($test->getId() == $resource->getId() && $test->getResourceType() == $resource->getResourceType()) return $k;
            }
        }
        throw new CollectionUndefinedIndexException("Resource is not in collection");
    }
    protected function remove2MRel($name, ResourceInterface $resource) {
        if (!in_array($name, $this->initializedRelationships)) $this->initialize2MRel($name);
        try {
            $index = $this->indexOf2MRel($name, $resource);
            $collection = $this->relationships[$name]->getData();
            unset($collection[$index]);
            $this->_setRelationship($name, $collection);
        } catch (CollectionUndefinedIndexException $e) {
            // Nothing to do if not in collection
        }
        return;
    }

    protected function initialize2MRel($name) {
        $collection = $this->datasource->getRelated($name, $this->getId());

        // Get any relationships that may have been added before the relationship was initialized
        $currentMembers = [];
        if ($this->relationships[$name] !== null && $this->relationships[$name]->getData()) {
            $currentMembers = $this->relationships[$name]->getData();
        }

        $this->trackChanges = false;
        $this->_setRelationship($name, $collection);
        $this->initializedRelationships[$name] = $name;
        $this->initialState['relationships'][$name] = $collection->summarize();
        $this->trackChanges = true;

        foreach($currentMembers as $rel) {
            $this->add2MRel($name, $rel);
        }
    }
}

