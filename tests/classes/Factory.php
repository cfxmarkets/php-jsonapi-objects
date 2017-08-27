<?php
namespace Test;

class Factory extends \KS\Factory implements \KS\JsonApi\FactoryInterface {
    use \KS\JsonApi\FactoryTrait {
        newResource as newGenericResource;
    }

    public function newResource($data=null, $initialized=true, $type=null) {
        return $this->instantiate("\\KS\\JsonApi\\Resource", [$this, $data, $initialized]);
    }

    protected function injectServices($obj) {
    }
}

