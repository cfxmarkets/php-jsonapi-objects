<?php
namespace KS\JsonApi;

interface RelationshipInterface {
    function getType();
    function getId();
    function getLinks();
}

