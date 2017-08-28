<?php
namespace KS\JsonApi;

interface ResourceInterface extends \JsonSerializable {
    function setAttribute(string $attr, $val);
    function setRelationship(Relationship $r);
    function getResourceType();
    function getId();
    function getAttributes();
    function getAttribute(string $k);
    function getRelationships();
    function getRelationship(string $k);
    function validateRelationship(string $rel);
    function validateAttribute(string $field);
    function validateResource();
}
