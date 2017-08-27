<?php
namespace KS\JsonApi;

interface FactoryInterface {
    function newDocument($data=null);
    function newResource($data=null, $initialized=true, $type=null);
    function newRelationship($data);
    function newError($data);
    function newResourceCollection($resources=[]);
    function newErrorsCollection($errors=[]);
}

