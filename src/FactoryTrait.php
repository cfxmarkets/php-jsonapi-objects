<?php
namespace KS\JsonApi;

trait FactoryTrait {
    function newDocument(array $data=null) {
        return $this->instantiate("\\KS\\JsonApi\\Document", [$this, $data]);
    }

    function newResource(array $data=null, bool $initialized=true, string $type=null) {
        if ($type !== null) throw new \UnknownResourceTypeException("Type `$type` is unknown. You can handle this type by overriding the `newResource` method in your factory and adding a handler for the type there.");
        return $this->instantiate("\\KS\\JsonApi\\Resource", [$this, $data, $initialized]);
    }

    function newRelationship(array $data) {
        return $this->instantiate("\\KS\\JsonApi\\Relationship", [$this, $data]);
    }

    function newError(array $data) {
        return $this->instantiate("\\KS\\JsonApi\\Error", [$this, $data]);
    }

    function newResourceCollection(array $resources=[]) {
        return $this->instantiate("\\KS\\JsonApi\\ResourceCollection", [$resources]);
    }

    function newErrorsCollection(array $errors=[]) {
        return $this->instantiate("\\KS\\JsonApi\\ErrorsCollection", [$errors]);
    }
}

