<?php
namespace KS\JsonApi;

interface ResourceInterface extends \JsonSerializable {
    function setAttribute($attr, $val);
    function setRelationship(Relationship $r);
    function getResourceType();
    function getId();
    function getAttributes();
    function getAttribute($k);
    function getRelationships();
    function getRelationship($k);
    function validateRelationship($rel);
    function validateAttribute($field);
    function validateResource();
}
