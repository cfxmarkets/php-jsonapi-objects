<?php

use \KS\JsonApi\Resource;
use \Test\Factory;

class ResourceTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateEmptyResource() {
        $f = Factory::getInstance();
        $t = new Resource($f);
        $this->assertTrue($t instanceof \KS\JsonApi\ResourceInterface, "Should instantiate a valid Resource object");
    }
}

