<?php
namespace KS\JsonApi;

interface LinkInterface extends NamedMemberInterface {
    function getHref();
    function getMeta();
    function setMeta(MetaInterface $meta);
    function setHref(string $href);
}

