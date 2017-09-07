<?php
namespace KS\JsonApi;

interface GenericResourceInterface extends BaseResourceInterface {
    function setAttribute(string $attr, $val);
    function getAttribute(string $k);
    function getAttributes();
    function setRelationship(Relationship $r);
    function getRelationship(string $k);
    function getRelationships();
}

