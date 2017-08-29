<?php
namespace KS\JsonApi;

interface FactoryInterface {
    function newJsonApiDocument(array $data=null);
    function newJsonApiResource(array $data=null, bool $initialized=true, string $type=null);
    function newJsonApiRelationship(array $data);
    function newJsonApiError(array $data);
    function newJsonApiResourceCollection(array $resources=[]);
    function newJsonApiErrorsCollection(array $errors=[]);
    function newJsonApiMeta(array $data=null);
    function newJsonApiLink(array $data=null);
}

