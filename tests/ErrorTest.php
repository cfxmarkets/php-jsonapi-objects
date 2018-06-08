<?php

use \CFX\JsonApi\Error;

class ErrorTest extends \PHPUnit\Framework\TestCase {
    public function testErrorThrowsErrorOnEmptyInstantiation() {
        try {
            $e = new Error();
            $this->fail("Should have thrown an error");
        } catch (\ArgumentCountError $e) {
            $this->assertTrue(true, "This is the expected behavior");
        }

        try {
            $e = new Error([]);
            $this->fail("Should have thrown an exception");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, "This is the expected behavior");
        }
    }

    public function testErrorShouldCreateValidError() {
        $e = new Error([
            'status' => 500,
            'title' => 'Server Error',
            'detail' => 'There was a server error',
        ]);

        $this->assertTrue($e instanceof Error, "Should have returned a valid Error object");
    }

    public function testErrorShouldValidateStatus() {
        try {
            $e = new Error([ 'status' => "Five Hundred", 'title' => 'Test title' ]);
            $this->fail("Should have thrown an exception");
        } catch (InvalidArgumentException $e) {
            $this->assertContains('`status`', $e->getMessage(), "Error should indicate errors in `status` field");
        }
    }

    public function testErrorShouldValidateLinksOnInstantiate() {
        $e = new Error([
            'status' => 500,
            'title' => "Server Error",
            "detail" => "There was an error",
            "links" => new \CFX\JsonApi\Collection([
                "about" => "https://test.com/about/error"
            ]),
        ]);
        $this->assertInstanceOf("\\CFX\\JsonApi\\CollectionInterface", $e->getLinks());
        $this->assertEquals(1, count($e->getLinks()));
        $this->assertInstanceOf("\\CFX\\JsonApi\\LinkInterface", $e->getLinks()['about']);
        $this->assertEquals("about", $e->getLinks()['about']->getName());
        $this->assertEquals("https://test.com/about/error", $e->getLinks()['about']->getHref());
    }

    /**
     * Per the [jsonapi spec](http://jsonapi.org/format/#errors), errors have an optional `source`
     * member with a certain specification. This test should validate that the Error class enforces
     * this specification.
     */
    public function testErrorShouldValidateSource() {
        $this->markTestIncomplete();
        // TODO: Instantiate error with source property
        // Should have 'pointer' property and 'parameter'
        
        $this->assertTrue(is_array($e->getSource()));
        $this->assertContains('pointer', array_keys($e->getSource()));
        $this->assertRegExp("#(/[^/]+)+#", $e->getSource()['pointer']);
        $this->assertContains('parameter', array_keys($e->getSource()));
        $this->assertRegExp("#[^/&= ]+#", $e->getSource()['parameter']);


        // TODO: Instantiate error with source property and NOT pointer or parameter

        $this->assertInstanceOf("\\CFX\\JsonApi\\ErrorInterface", $e);
        $this->assertNotContains('pointer', array_keys($e->getSource()));
        $this->assertNotContains('parameter', array_keys($e->getSource()));
        $this->assertTrue(count($e->getSource()) > 0);
    }

    public function testErrorShouldValidateMeta() {
        $this->markTestIncomplete();
    }

    public function testErrorRequiredValues() {
        try {
            $e = new Error([ 'status' => 200 ]);
            $this->fail("Should have thrown an exception");
        } catch (InvalidArgumentException $e) {
            $this->assertContains('`title`', $e->getMessage(), "Error should indicate that title is a required field.");
        }

        try {
            $e = new Error([ 'title' => "This is an error" ]);
            $this->fail("Should have thrown an exception");
        } catch (InvalidArgumentException $e) {
            $this->assertContains('`status`', $e->getMessage(), "Error should indicate that status is a required field.");
        }
    }

    public function testErrorShouldThrowExceptionOnMalformedData() {
        try {
            new Error(['status' => 400, 'title' => 'some title', 'detail' => 'some detail', 'extra' => 'extra!!!']);
            $this->fail("Should have thrown an exception");
        } catch(\CFX\JsonApi\MalformedDataException $e) {
            $this->assertContains("`extra`", $e->getMessage());
            $this->assertEquals("Error (`some title`)", $e->getOffenders()[0]);
            $this->assertEquals(['extra'=>'extra!!!'], $e->getOffendingData());
        }
    }

    public function testErrorShouldSerializeWell() {
        $e = new Error([
            'status' => 200,
            'title' => 'All Cool',
        ]);
        $json = json_encode($e);
        $this->assertContains('"status":200', $json, 'Should contain status field');
        $this->assertContains('"title":"All Cool"', $json, 'Should contain title field');
    }
}

