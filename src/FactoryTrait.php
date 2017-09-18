<?php
namespace KS\JsonApi;

trait FactoryTrait {
    function newJsonApiDocument(array $data=null) { return new Document($this, $data); }
    function newJsonApiResource(array $data=null, string $type=null, array $validAttrs=null, array $validRels=null) {
        if ($type !== null) throw new UnknownResourceTypeException("Type `$type` is unknown. You can handle this type by overriding the `newJsonApiResource` method in your factory and adding a handler for the type there.");
        return new GenericResource($this, $data, $validAttrs, $validRels);
    }
    function newJsonApiRelationship(array $data) { return new Relationship($this, $data); }
    function newJsonApiError(array $data) { return new Error($this, $data); }
    function newJsonApiResourceCollection(array $resources=[]) { return new ResourceCollection($resources); }
    function newJsonApiErrorsCollection(array $errors=[]) { return new ErrorsCollection($errors); }
    function newJsonApiMeta(array $data=null) { return new Meta($data); }
    function newJsonApiLink(array $data=null) { return new Link($this, $data); }
    function newJsonApiLinksCollection(array $links=[]) { return new LinksCollection($links); }
}

