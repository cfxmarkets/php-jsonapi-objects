<?php
namespace KS\JsonApi;

interface BaseResourceInterface extends \JsonSerializable, \KS\ErrorHandlerInterface {
    function getResourceType();
    function getId();
    function setId(string $id);
    function updateFromUserInput(array $data);
}

