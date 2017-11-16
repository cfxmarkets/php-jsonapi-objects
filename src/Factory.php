<?php
namespace CFX\JsonApi;

class Factory implements FactoryInterface {
    public function newDocument(array $data=null) { return new Document($data); }
    public function newResource(array $data=null, $type=null) { return new GenericResource($data); }
    public function newResourceIdentifier(array $data=null) { return new ResourceIdentifier($data); }
    public function newRelationship(array $data) { return new Relationship($data); }
    public function newError(array $data) { return new Error($data); }
    public function newMeta(array $data=null) { return new Meta($data); }
    public function newLink(array $data=null) { return new Link($data); }
    public function newResourceCollection(array $resources=[]) { return new ResourceCollection($resources); }
    public function newErrorsCollection(array $errors=[]) { return new ErrorsCollection($errors); }
    public function newLinksCollection(array $links=[]) { return new LinksCollection($links); }

    public function resourceIdentifierFromResource(ResourceInterface $r) { return ResourceIdentifier::fromResource($r); }
}

