<?php

use \KS\JsonApi\GenericResource;
use \Test\Factory;

class ResourceTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateEmptyResource() {
        $f = Factory::getInstance();
        $t = new GenericResource($f);
        $this->assertTrue($t instanceof \KS\JsonApi\GenericResourceInterface, "Should instantiate a valid GenericResource object");
    }
}

