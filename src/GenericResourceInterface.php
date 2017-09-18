<?php
namespace KS\JsonApi;

interface GenericResourceInterface extends BaseResourceInterface {
    function setAttribute($attr, $val);
    function getAttribute($k);
    function getAttributes();
    function setRelationship(RelationshipInterface $r);
    function getRelationship($k);
    function getRelationships();
}
