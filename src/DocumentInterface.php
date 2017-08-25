<?php
namespace KS\JsonApi;

interface DocumentInterface extends \JsonSerializable {
    function getData();
    function getErrors();
    function getLinks();
    function getMeta();
    function getJsonApi();
    function setData($data);
    function addError(Error $e);
}

