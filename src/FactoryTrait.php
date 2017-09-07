<?php
namespace KS\JsonApi;

trait FactoryTrait {
    function newJsonApiDocument(array $data=null) {
        return $this->instantiate("\\KS\\JsonApi\\Document", [$this, $data]);
    }

    function newJsonApiResource(array $data=null, bool $initialized=true, string $type=null) {
        if ($type !== null) throw new UnknownResourceTypeException("Type `$type` is unknown. You can handle this type by overriding the `newJsonApiResource` method in your factory and adding a handler for the type there.");
        return $this->instantiate("\\KS\\JsonApi\\GenericResource", [$this, $data, $initialized]);
    }

    function newJsonApiRelationship(array $data) {
        return $this->instantiate("\\KS\\JsonApi\\Relationship", [$this, $data]);
    }

    function newJsonApiError(array $data) {
        return $this->instantiate("\\KS\\JsonApi\\Error", [$this, $data]);
    }

    function newJsonApiResourceCollection(array $resources=[]) {
        return $this->instantiate("\\KS\\JsonApi\\ResourceCollection", [$resources]);
    }

    function newJsonApiErrorsCollection(array $errors=[]) {
        return $this->instantiate("\\KS\\JsonApi\\ErrorsCollection", [$errors]);
    }

    function newJsonApiMeta(array $data=null) {
        return $this->instantiate("\\KS\\JsonApi\\Meta", [$data]);
    }

    function newJsonApiLink(array $data=null) {
        return $this->instantiate("\\KS\\JsonApi\\Link", [$this, $data]);
    }

    function newJsonApiLinksCollection(array $links=[]) {
        return $this->instantiate("\\KS\\JsonApi\\LinksCollection", [$links]);
    }
}

