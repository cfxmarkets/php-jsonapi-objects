<?php
namespace KS\JsonApi;

interface ErrorInterface extends \JsonSerializable {
    function getId();
    function getLinks();
    function getStatus();
    function getCode();
    function getTitle();
    function getDetail();
    function getSource();
    function getMeta();
}

