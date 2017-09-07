<?php
namespace Test;

class Factory extends \KS\Factory implements \KS\JsonApi\FactoryInterface {
    use \KS\JsonApi\FactoryTrait {
        newJsonApiResource as newGenericJsonApiResource;
    }

    /**
     * Stub this out so that we return a GenericResource for any requested type
     */
    public function newJsonApiResource($data=null, $initialized=true, $type=null) {
        return $this->instantiate("\\KS\\JsonApi\\GenericResource", [$this, $data, $initialized]);
    }

    protected function injectServices($obj) {
    }
}

