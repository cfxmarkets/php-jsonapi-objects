<?php

use \KS\JsonApi\Error;

class ErrorTest extends \PHPUnit\Framework\TestCase {
    public function testErrorThrowsErrorOnEmptyInstantiation() {
        try {
            $e = new Error();
            $this->fail("Should have thrown an error");
        } catch (ArgumentCountError $e) {
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

    public function testErrorShouldValidateLinks() {
    }

    public function testErrorShouldValidateSource() {
    }

    public function testErrorShouldValidateMeta() {
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

