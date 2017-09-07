<?php
namespace KS\JsonApi;

interface FactoryInterface {
    function newJsonApiDocument(array $data=null);
    function newJsonApiResource(array $data=null, string $type=null);
    function newJsonApiRelationship(array $data);
    function newJsonApiError(array $data);
    function newJsonApiMeta(array $data=null);
    function newJsonApiLink(array $data=null);
    function newJsonApiResourceCollection(array $resources=[]);
    function newJsonApiErrorsCollection(array $errors=[]);
    function newJsonApiLinksCollection(array $links=[]);
}

