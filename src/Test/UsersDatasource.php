<?php
namespace CFX\JsonApi\Test;

class UsersDatasource implements \CFX\JsonApi\DatasourceInterface {
    protected $currentData = null;
    protected $testData = [];

    public function setTestData($key, $data) {
        if (!array_key_exists($key, $this->testData)) $this->testData[$key] = [];
        $this->testData[$key][] = $data;
        return $this;
    }
    protected function getTestData($key, $explanation=null) {
        if (!array_key_exists($key, $this->testData)) $this->testData[$key] = [];
        if (count($this->testData[$key]) == 0) throw new \RuntimeException("Programmer: You need to set test data for `$key` using the `setTestData` method of your test datasource. $explanation");
        
        $data = array_pop($this->testData[$key]);

        if ($data instanceof \Exception) throw $data;

        return $data;
    }


    public function getCurrentData() {
        $data = $this->currentData;
        $this->currentData = null;
        return $data;
    }

    public function create(array $data=null) {
        return new User($this, $data);
    }

    public function newCollection(array $collection=null) {
        return new \CFX\JsonApi\ResourceCollection($collection);
    }

    public function convert(\CFX\JsonApi\ResourceInterface $src, $convertTo) {
        if ($convertTo == 'private') {
            return PrivateUser::fromResource($src);
        } elseif ($convertTo == 'public') {
            return User::fromResource($src);
        } else {
            throw new \RuntimeException("Don't know how to convert to `$convertTo` type resources.");
        }
    }

    public function save(\CFX\JsonApi\ResourceInterface $r) {
        $result = $this->getTestData('save', 'Data should be a jsonapi-formatted array like what you would get back from the API on save');

        try {
            $r->restoreFromData($result);
        } catch (\CFX\JsonApi\MalformedDataException $e) {
            throw new \RuntimeException("Hm... Looks like you might not have input the right kind of test data. You need to provide a valid jsonapi-formatted representation of a ".get_class($r)." resource.", 0, $e);
        }

        return $this;
    }

    public function get($q=null) {
        $data = $this->getTestData("get-$q");

        $keys = array_keys($data);
        $isCollection = true;
        for($i = 0, $c = count($keys); $i < $c; $i++) {
            if (!is_int($keys[$i])) {
                $isCollection = false;
                break;
            }
        }

        if (!$isCollection) {
            $data = [$data];
        }

        foreach($data as $k => $o) {
            $this->currentData = $o;
            $data[$k] = $this->create();
        }

        if ($isCollection) {
            return $this->newCollection($data);
        } else {
            return $data[0];
        }
    }

    public function delete($r) {
        return $this;
    }

    public function inflateRelated(array $data) {
        if ($data['type'] == 'test-users') return $this->create($data);
        else return new \CFX\JsonApi\GenericResource($this, $data);
    }
}

