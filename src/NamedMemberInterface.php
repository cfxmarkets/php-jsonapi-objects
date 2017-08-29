<?php
namespace KS\JsonApi;

interface NamedMemberInterface extends \JsonSerializable {
    function getMemberName();
}

