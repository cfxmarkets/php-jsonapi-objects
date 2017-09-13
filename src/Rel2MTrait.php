<?php
namespace KS\JsonApi;

trait Rel2MTrait {
    protected function add2MRel(string $name, BaseResourceInterface $resource) {
        if (!$this->has2MRel($resource)) $this->relationships[$name]->getData()[] = $resource;
        return $this;
    }
    protected function has2MRel(string $name, BaseResourceInterface $resource=null) {
        if (!$resource || !$this->relationships[$name]->getData()) return false;
        foreach($this->relationships[$name]->getData() as $test) {
            if ($test->getId() == $resource->getId() && $test->getResourceType() == $resource->getResourceType()) return true;
        }
        return false;
    }
    protected function remove2MRel(string $name, BaseResourceInterface $resource) {
        if (!$this->relationships[$name]->getData()) return;
        foreach($this->relationships[$name]->getData() as $k => $test) {
            if ($test->getId() == $resource->getId() && $test->getResourceType() == $resource->getResourceType()) {
                unset($this->relationships[$name]->getData()[$k]);
                break;
            }
        }
    }
}

