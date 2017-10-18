<?php
namespace KS\JsonApi;

interface ContextInterface {
    function newJsonApiDocument($data=null);
    function newJsonApiResource($data=null, $type=null);
    function convertJsonApiResource(ResourceInterface $src, $conversionType);
    function newJsonApiRelationship($data);
    function newJsonApiError($data);
    function newJsonApiMeta($data=null);
    function newJsonApiLink($data=null);
    function newJsonApiResourceCollection($resources=[]);
    function newJsonApiErrorsCollection($errors=[]);
    function newJsonApiLinksCollection($links=[]);
    function getCurrentData();
}

