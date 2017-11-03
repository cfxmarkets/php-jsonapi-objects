<?php
namespace CFX\JsonApi\Test;

class MockDatasource implements \CFX\JsonApi\DatasourceInterface
{
    protected $currentData = null;
    protected $callStack = [];

    public function getCurrentData()
    {
        $data = $this->currentData;
        $this->currentData = null;
        return $data;
    }

    public function getCallStack()
    {
        $stack = $this->callStack;
        $this->callStack = [];
        return $stack;
    }

    public function create(array $data = null, $type = null)
    {
        $this->callStack[] = "create([data], '$type')";
        return new \CFX\JsonApi\GenericResource($this, $data);
    }

    public function newCollection(array $collection=null)
    {
        $this->callStack[] = "newCollection([elements])";
        return new \CFX\JsonApi\ResourceCollection($collection);
    }

    public function convert(\CFX\JsonApi\ResourceInterface $src, $convertTo)
    {
        $this->callStack[] = "convert([resource], '$convertTo')";
        return $src;
    }

    public function save(\CFX\JsonApi\ResourceInterface $r)
    {
        $this->callStack[] = "save([resource])";
        return $this;
    }

    public function get($q=null)
    {
        $this->callStack[] = "get('$q')";
        if ($q) {
            return $this->create();
        } else {
            return $this->newCollection();
        }
    }

    public function getRelated($name, $id)
    {
        $this->callStack[] = "getRelated('$name', '$id')";
        return $this->create();
    }

    public function delete($r)
    {
        if ($r instanceof \CFX\JsonApi\ResourceInterface) {
            $r = $r->getId();
        }
        $this->callStack[] = "delete('$r')";
        return $this;
    }

    public function inflateRelated(array $data)
    {
        $this->callStack[] = "inflateRelated([data])";
        return $this->create($data);
    }

    public function initializeResource(\CFX\JsonApi\ResourceInterface $r)
    {
        $this->callStack[] = "initializeResource('{$r->getResourceType()}({$r->getId()}')";
        $this->currentData = [];
        $r->restoreFromData();
        return $this;
    }
}




