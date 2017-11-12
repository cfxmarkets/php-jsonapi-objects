<?php
namespace CFX\JsonApi;

interface GenericResourceInterface extends ResourceInterface {
    function setAttribute($attr, $val);
    function getAttribute($k);
    function getAttributes();
    function setRelationship($name, $r = null);
    function getRelationship($k);
    function getRelationships();
}

