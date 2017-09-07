<?php
namespace Test;

class Factory extends \KS\Factory implements \KS\JsonApi\FactoryInterface {
    use \KS\JsonApi\FactoryTrait {
        newJsonApiResource as newGenericJsonApiResource;
    }

    /**
     * Stub this out so that we return a GenericResource for any requested type
     */
    public function newJsonApiResource($data=null, $type=null) {
        $currentArgs = func_get_args();
        $args = [$this];
        for($i = 0; $i < count($currentArgs); $i++) {
            if ($i == 1) continue;
            $args[] = $currentArgs[$i];
        }

        if ($type == 'test-users') return $this->instantiate("\\Test\\User", $args);
        return $this->instantiate("\\KS\\JsonApi\\GenericResource", $args);
    }

    protected function injectServices($obj) {
    }
}

