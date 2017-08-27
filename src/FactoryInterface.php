<?php
namespace KS\JsonApi;

interface FactoryInterface {
    function newDocument(array $data=null);
    function newResource(array $data=null, bool $initialized=true, string $type=null);
    function newRelationship(array $data);
    function newError(array $data);
    function newResourceCollection(array $resources=[]);
    function newErrorsCollection(array $errors=[]);
}

