<?php
namespace KS\JsonApi;

interface DocumentInterface extends \JsonSerializable {
    function getData();
    function getErrors();
    function getLinks();
    function getLink(string $name);
    function getMeta();
    function setData($data);
    function addError(ErrorInterface $e);
    function addLink(LinkInterface $l);
    function setMeta(MetaInterface $m);
}

