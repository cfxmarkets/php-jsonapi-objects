<?php
namespace KS\JsonApi;

interface FactoryInterface {
    function newJsonApiDocument($data=null);
    function newJsonApiResource($data=null, $initialized=true, $type=null);
    function newJsonApiRelationship($data);
    function newJsonApiError($data);
    function newJsonApiMeta($data=null);
    function newJsonApiLink($data=null);
    function newJsonApiResourceCollection($resources=[]);
    function newJsonApiErrorsCollection($errors=[]);
    function newJsonApiLinksCollection($links=[]);
}

