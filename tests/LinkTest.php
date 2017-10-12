<?php

use \KS\JsonApi\Link;

class LinkTest extends \PHPUnit\Framework\TestCase {
    public function testThrowsErrorIfNoFactoryOnConstruct() {
        try {
            $l = new Link();
            $this->fail("Should have thrown error");
        } catch (\PHPUnit_Framework_Error $e) {
            $this->assertTrue(true, "This is the desired behavior");
        }
    }

    public function testCanCreateNewEmptyLink() {
        $l = new Link(new \KS\JsonApi\Test\Factory());
        $this->assertTrue($l instanceof \KS\JsonApi\LinkInterface, "Should have returned an implementation of LinkInterface");
    }

    public function testInterface() {
        $l = new Link(new \KS\JsonApi\Test\Factory());
        $l->setName("test");
        $l->setHref("/relative/uri");
        $l->setMeta(new \KS\JsonApi\Meta([
            'test-object' => [
                'test1' => 1,
                'test2' => 2,
                'test3' => 3,
            ],
            'test-bool' => true,
        ]));

        $this->assertEquals('test', $l->getName());
        $this->assertEquals('test', $l->getMemberName());
        $this->assertEquals('/relative/uri', $l->getHref());
        $this->assertEquals(1, $l->getMeta()['test-object']['test1']);
        $this->assertTrue(is_string(json_encode($l)) && strlen(json_encode($l)) > 0);
    }

    public function testCorrectlyHandlesDataOnInstantiate() {
        $l = new Link(new \KS\JsonApi\Test\Factory(), [
            'name' => 'test',
            'href' => '/test/me',
            'meta' => new \KS\JsonApi\Meta(['test1' => 1]),
        ]);
        $this->assertEquals('test', $l->getName());
        $this->assertEquals('/test/me', $l->getHref());
        $this->assertEquals(1, $l->getMeta()['test1']);

        $l = new Link(new \KS\JsonApi\Test\Factory(), [ 'name' => 'test' ]);
        $this->assertEquals('test', $l->getName());
        $this->assertNull($l->getHref());
        $this->assertNull($l->getMeta());

        $l = new Link(new \KS\JsonApi\Test\Factory(), [ 'href' => '/test/me' ]);
        $this->assertEquals('/test/me', $l->getHref());
        $this->assertNull($l->getName());
        $this->assertNull($l->getMeta());

        $l = new Link(new \KS\JsonApi\Test\Factory(), [ 'meta' => ['test1' => 1]]);
        $this->assertTrue($l->getMeta() instanceof \KS\JsonApi\Meta);
        $this->assertEquals(1, $l->getMeta()['test1']);
        $this->assertNull($l->getName());
        $this->assertNull($l->getHref());

        try {
            new Link(new \KS\JsonApi\Test\Factory(), [ 'name' => 'test', 'href' => '/test/me', 'invalid' => 'extra!!!' ]);
            $this->fail("Should have thrown an exception");
        } catch(\KS\JsonApi\MalformedDataException $e) {
            $this->assertContains("`invalid`", $e->getMessage());
            $this->assertEquals("Link (`test`)", $e->getOffender());
            $this->assertEquals(['invalid'=>'extra!!!'], $e->getOffendingData());
        }
    }

    public function testEmptyLinkSerializesToNull() {
        $l = new Link(new \KS\JsonApi\Test\Factory());
        $this->assertNull($l->jsonSerialize());
    }

    public function testSerializesToObjectIfMetaPresentAndStringOtherwise() {
        $l = new Link(new \KS\JsonApi\Test\Factory(), [ 'name' => 'test', 'href' => '/test/me' ]);
        $this->assertEquals("/test/me", $l->jsonSerialize());

        $l = new Link(new \KS\JsonApi\Test\Factory(), [ 'name' => 'test', 'href' => '/test/me', 'meta' => ['test1' => 1]]);
        $this->assertEquals('{"href":"\\/test\\/me","meta":{"test1":1}}', json_encode($l));
    }
}

