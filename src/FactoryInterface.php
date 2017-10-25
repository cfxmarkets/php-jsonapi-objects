<?php
namespace CFX\JsonApi;
 
interface FactoryInterface {
    public function newDocument(array $data=null);
    public function newResource(array $data=null, $type=null);
    public function newRelationship(array $data);
    public function newError(array $data);
    public function newMeta(array $data=null);
    public function newLink(array $data=null);
    public function newResourceCollection(array $resources=[]);
    public function newErrorsCollection(array $errors=[]);
    public function newLinksCollection(array $links=[]);
}

