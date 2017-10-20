<?php
namespace KS\JsonApi;

interface ResourceInterface extends \JsonSerializable, \KS\ErrorHandlerInterface {
    function getResourceType();
    function getId();
    function setId($id);
    function updateFromJsonApi(array $data);
    function getChanges();
    function hasChanges();
    function save();
}

