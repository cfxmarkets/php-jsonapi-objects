<?php
namespace KS\JsonApi;

interface GenericResourceInterface extends BaseResourceInterface {
    function setAttribute(string $attr, $val);
    function getAttribute(string $k);
    function getAttributes();
    function setRelationship(RelationshipInterface $r);
    function getRelationship(string $k);
    function getRelationships();
}

