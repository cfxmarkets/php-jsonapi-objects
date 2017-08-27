<?php
namespace KS\JsonApi;

trait FactoryTrait {
    function newDocument($data=null) {
        return $this->instantiate("\\KS\\JsonApi\\Document", [$this, $data]);
    }

    function newResource($data=null, $initialized=true, $type=null) {
        if ($type !== null) throw new \UnknownResourceTypeException("Type `$type` is unknown. You can handle this type by overriding the `newResource` method in your factory and adding a handler for the type there.");
        return $this->instantiate("\\KS\\JsonApi\\Resource", [$this, $data, $initialized]);
    }

    function newRelationship($data) {
        return $this->instantiate("\\KS\\JsonApi\\Relationship", [$this, $data]);
    }

    function newError($data) {
        return $this->instantiate("\\KS\\JsonApi\\Error", [$this, $data]);
    }

    function newResourceCollection($resources=[]) {
        return $this->instantiate("\\KS\\JsonApi\\ResourceCollection", [$resources]);
    }

    function newErrorsCollection($errors=[]) {
        return $this->instantiate("\\KS\\JsonApi\\ErrorsCollection", [$errors]);
    }
}

