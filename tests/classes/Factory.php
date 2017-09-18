<?php
namespace Test;

class Factory implements \KS\JsonApi\FactoryInterface {
    use \KS\JsonApi\FactoryTrait {
        newJsonApiResource as newGenericJsonApiResource;
    }

    /**
     * Stub this out so that we return a GenericResource for any requested type
     */
    public function newJsonApiResource($data=null, $type=null) {
        if ($type == 'test-users') return new User($this, $data);
        return new \KS\JsonApi\GenericResource($this, $data);
    }
}

