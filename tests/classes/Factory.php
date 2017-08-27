<?php
namespace Test;

class Factory extends \KS\Factory implements \KS\JsonApi\FactoryInterface {
    use \KS\JsonApi\FactoryTrait {
        newResource as newGenericResource;
    }

    public function newResource(array $data=null, bool $initialized=true, string $type=null) {
        return $this->instantiate("\\KS\\JsonApi\\Resource", [$this, $data, $initialized]);
    }

    protected function injectServices($obj) {
    }
}

