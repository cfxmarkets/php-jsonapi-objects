<?php
namespace KS\JsonApi;

trait FactoryTrait {
    function newDocument(array $data=null) {
        return $this->instantiate("\\KS\\JsonApi\\Document", [$this, $data]);
    }

    function newResource(array $data=null, bool $initialized=true) {
        return $this->instantiate("\\KS\\JsonApi\\Resource", [$this, $data, $initialized]);
    }

    function newRelationship(array $data) {
        return $this->instantiate("\\KS\\JsonApi\\Relationship", [$this, $data]);
    }

    function newError(array $data) {
        return $this->instantiate("\\KS\\JsonApi\\Error", [$this, $data]);
    }

    function newResourceCollection(array $resources=null) {
        return $this->instantiate("\\KS\\JsonApi\\ResourceCollection", [$this, $resources]);
    }

    function newErrorsCollection(array $errors=null) {
        return $this->instantiate("\\KS\\JsonApi\\ErrorsCollection", [$this, $errors]);
    }
}

