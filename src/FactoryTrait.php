<?php
namespace KS\JsonApi;

trait FactoryTrait {
    function newJsonApiDocument($data=null) { return new Document($this, $data); }
    function newJsonApiResource($data=null, $type=null, $validAttrs=null, $validRels=null) {
        if ($type !== null) throw new UnknownResourceTypeException("Type `$type` is unknown. You can handle this type by overriding the `newJsonApiResource` method in your factory and adding a handler for the type there.");
        return new GenericResource($this, $data, $validAttrs, $validRels);
    }
    function newJsonApiRelationship($data) { return new Relationship($this, $data); }
    function newJsonApiError($data) { return new Error($this, $data); }
    function newJsonApiResourceCollection($resources=[]) { return new ResourceCollection($resources); }
    function newJsonApiErrorsCollection($errors=[]) { return new ErrorsCollection($errors); }
    function newJsonApiMeta($data=null) { return new Meta($data); }
    function newJsonApiLink($data=null) { return new Link($this, $data); }
    function newJsonApiLinksCollection($links=[]) { return new LinksCollection($links); }
}

