<?php
namespace KS\JsonApi;

interface GenericResourceInterface extends BaseResourceInterface {
    function setAttribute($attr, $val);
    function getAttribute($k);
    function getAttributes();
    function setRelationship(Relationship $r);
    function getRelationship($k);
    function getRelationships();
}

