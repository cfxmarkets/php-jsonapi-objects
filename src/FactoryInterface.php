<?php
namespace CFX\JsonApi;
 
interface FactoryInterface {
    public function newDocument($data=null);
    public function newResource($data=null, $type=null);
    public function newRelationship($data);
    public function newError($data);
    public function newMeta($data=null);
    public function newLink($data=null);
    public function newResourceCollection($resources=[]);
    public function newErrorsCollection($errors=[]);
    public function newLinksCollection($links=[]);
}

