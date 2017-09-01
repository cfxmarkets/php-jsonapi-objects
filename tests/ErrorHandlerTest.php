<?php

use \KS\JsonApi\Error;

class ErrorHandlerTest extends \PHPUnit\Framework\TestCase {
    public function testErrors() {
        $f = \Test\Factory::getInstance();
        $t = new \Test\TestErrorHandler();

        $this->assertFalse($t->hasErrors());
        $this->assertFalse($t->hasErrors('testField'));
        $this->assertEquals(0, $t->numErrors());
        $this->assertEquals(0, $t->numErrors('testField'));
        $this->assertEquals([], $t->getErrors());
        $this->assertEquals([], $t->getErrors('testField'));

        $t->produceError('testField', null, $f->newJsonApiError(['title' => 'Bad Email', 'detail' => 'Email is bad', 'status' => 400]));
        $this->assertTrue($t->hasErrors());
        $this->assertTrue($t->hasErrors('testField'));
        $this->assertFalse($t->hasErrors('testField2'));
        $this->assertEquals(1, $t->numErrors());
        $this->assertEquals(1, $t->numErrors('testField'));
        $this->assertContains('Email is bad', json_encode($t->getErrors()));
        $this->assertContains('Email is bad', json_encode($t->getErrors('testField')));

        $t->produceError('testField', 'email-required', $f->newJsonApiError(['title' => "Email Required", "detail" => 'Email is required', "status" => 400 ]));
        $this->assertTrue($t->hasErrors());
        $this->assertTrue($t->hasErrors('testField'));
        $this->assertFalse($t->hasErrors('testField2'));
        $this->assertEquals(2, $t->numErrors());
        $this->assertEquals(2, $t->numErrors('testField'));
        $this->assertContains('Email is bad', json_encode($t->getErrors()));
        $this->assertContains('Email is required', json_encode($t->getErrors()));
        $this->assertContains('Email is required', json_encode($t->getErrors('testField')));

        $t->produceError('testField', 'email-required', $f->newJsonApiError(['title' => "Email Seriously REQUIRED", 'detail' => 'Email is seriously required', 'status' => 400 ]));
        $this->assertTrue($t->hasErrors());
        $this->assertTrue($t->hasErrors('testField'));
        $this->assertFalse($t->hasErrors('testField2'));
        $this->assertEquals(2, $t->numErrors());
        $this->assertEquals(2, $t->numErrors('testField'));
        $this->assertContains('Email is bad', json_encode($t->getErrors()));
        $this->assertContains('Email is seriously required', json_encode($t->getErrors()));

        $t->deleteError('testField', 'email-required');
        $this->assertTrue($t->hasErrors());
        $this->assertTrue($t->hasErrors('testField'));
        $this->assertFalse($t->hasErrors('testField2'));
        $this->assertEquals(1, $t->numErrors());
        $this->assertEquals(1, $t->numErrors('testField'));
        $this->assertContains('Email is bad', json_encode($t->getErrors()));
        $this->assertContains('Email is bad', json_encode($t->getErrors('testField')));

        $t->produceError('testField2', 'name-required', $f->newJsonApiError([ 'title' => 'Name Required', 'detail' => 'Name is required', 'status' => 400 ]));
        $this->assertTrue($t->hasErrors());
        $this->assertTrue($t->hasErrors('testField'));
        $this->assertTrue($t->hasErrors('testField2'));
        $this->assertEquals(2, $t->numErrors());
        $this->assertEquals(1, $t->numErrors('testField'));
        $this->assertEquals(1, $t->numErrors('testField2'));
        $this->assertContains('Email is bad', json_encode($t->getErrors()));
        $this->assertContains('Name is required', json_encode($t->getErrors()));

        $t->deleteAllErrors();
        $this->assertFalse($t->hasErrors());
        $this->assertFalse($t->hasErrors('testField'));
        $this->assertFalse($t->hasErrors('testField2'));
        $this->assertEquals(0, $t->numErrors());
        $this->assertEquals([], $t->getErrors());
    }

    public function testThrowsExceptionOnNonJsonApiError() {
        $t = new \Test\TestErrorHandler();
        try {
            $t->produceError('testField', null, 'Some message');
            $this->fail("Should have thrown an exception");
        } catch (\TypeError $e) {
            $this->assertContains("KS\JsonApi\ErrorInterface", $e->getMessage(), "This is the expected behavior");
        }
    }
}

