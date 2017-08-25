<?php
namespace KS\JsonApi;

interface RelationshipInterface extends \JsonSerializable {
    function getName();
    function getLinks();
    function getMeta();
    function getData();
}

