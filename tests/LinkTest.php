<?php

use \KS\JsonApi\Link;

class LinkTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateNewEmptyLink() {
        $l = new Link(\Test\Factory::getInstance());
    }

    public function testEmptyLinkSerializesToNull() {
    }

    public function testLinkWithoutNameThrowsErrorOnSerialize() {
    }
}

