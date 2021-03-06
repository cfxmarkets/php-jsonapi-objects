<?php
namespace CFX\JsonApi\Test;

class MockDatasource implements \CFX\JsonApi\DatasourceInterface
{
    protected $currentData = null;
    protected $callStack = [];
    protected $creationStack = [];
    protected $debug = false;
    protected $related = [];

    public function setCurrentData($data)
    {
        $this->currentData = $data;
        return $this;
    }

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

    public function setDebug($debug)
    {
        $this->debug = $debug;
        return $this;
    }

    public function addClassToCreate($className)
    {
        if (is_array($className)) {
            $this->creationStack = array_merge($this->creationStack, $className);
        } else {
            $this->creationStack[] = $className;
        }
        return $this;
    }

    public function create(array $data = null, $type = null)
    {
        $this->callStack[] = "create([data], '$type')";
        if (!($classname = array_shift($this->creationStack))) {
            $classname = "\\CFX\\JsonApi\\GenericResource";
        }

        if ($this->debug) {
            echo "\nCreating class `$classname`";
        }

        return new $classname($this, $data);
    }

    public function newCollection(array $collection = [])
    {
        $this->callStack[] = "newCollection([elements])";
        return new \CFX\JsonApi\ResourceCollection($collection);
    }

    public function convert(\CFX\JsonApi\ResourceInterface $src, $convertTo)
    {
        $this->callStack[] = "convert([resource], '$convertTo')";
        if (!($classname = array_shift($this->creationStack))) {
            throw new \RuntimeException(
                "Since this is a mock datasource and not a real one, you need to actually add the class to create to ".
                "the creation stack using the `addClassToCreate` method. This class should be the class you want to convert ".
                "the resource to."
            );
        }
        $converted = $classname::fromResource($src, $this);
        return $converted;
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

    public function setRelated($name, \CFX\JsonApi\DataInterface $r)
    {
        $this->related[$name] = $r;
    }

    public function getRelated($name, $id)
    {
        $this->callStack[] = "getRelated('$name', '$id')";
        if (!array_key_exists($name, $this->related)) {
            throw new \RuntimeException("You need to add a related resource or collection of type `$name`. You can do this by calling `\$datasource->setRelated('$name', [resource])`");
        }
        $r = $this->related[$name];
        unset($this->related[$name]);
        return $r;
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
        $this->callStack[] = "initializeResource({$r->getResourceType()}({$r->getId()}))";
        $this->currentData = [
            "id" => $r->getId()
        ];
        $r->restoreFromData();
        return $this;
    }
}




