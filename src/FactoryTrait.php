<?php
namespace KS\JsonApi;

trait FactoryTrait {
    function newJsonApiDocument($data=null) {
        return $this->instantiate("\\KS\\JsonApi\\Document", [$this, $data]);
    }

    function newJsonApiResource($data=null, $initialized=true, $type=null) {
        if ($type !== null) throw new \UnknownResourceTypeException("Type `$type` is unknown. You can handle this type by overriding the `newJsonApiResource` method in your factory and adding a handler for the type there.");
        return $this->instantiate("\\KS\\JsonApi\\Resource", [$this, $data, $initialized]);
    }

    function newJsonApiRelationship($data) {
        return $this->instantiate("\\KS\\JsonApi\\Relationship", [$this, $data]);
    }

    function newJsonApiError($data) {
        return $this->instantiate("\\KS\\JsonApi\\Error", [$this, $data]);
    }

    function newJsonApiResourceCollection($resources=[]) {
        return $this->instantiate("\\KS\\JsonApi\\ResourceCollection", [$resources]);
    }

    function newJsonApiErrorsCollection($errors=[]) {
        return $this->instantiate("\\KS\\JsonApi\\ErrorsCollection", [$errors]);
    }

    function newJsonApiMeta($data=null) {
        return $this->instantiate("\\KS\\JsonApi\\Meta", [$data]);
    }

    function newJsonApiLink($data=null) {
        return $this->instantiate("\\KS\\JsonApi\\Link", [$this, $data]);
    }

    function newJsonApiLinksCollection($links=[]) {
        return $this->instantiate("\\KS\\JsonApi\\LinksCollection", [$links]);
    }
}

