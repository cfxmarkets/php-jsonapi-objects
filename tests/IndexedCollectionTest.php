<?php

use \Test\IndexedCollection;

class IndexedCollectionTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateEmptyCollection() {
        $t = new IndexedCollection();
        $this->assertTrue($t instanceof \KS\JsonApi\IndexedCollectionInterface, "Should have created an indexed collection");
    }

    public function testThrowsErrorOnNonNamedMemeber() {
        $t = new IndexedCollection();
        try {
            $t['test'] = (object)[];
            $this->fail('Should have thrown an exception adding non-named member');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true, "This is the expected behavior");
        }
    }

    public function testAcceptsNamedMember() {
        $t = new IndexedCollection();
        $t['test'] = new \Test\NamedMember();
        $this->assertTrue($t['test'] instanceof \KS\JsonApi\NamedMemberInterface, "Should hold an instance of NamedMemberInterface");
    }

    public function testThrowsErrorOnSerializeWithUnnamedMembers() {
        $t = new IndexedCollection();
        $t['test'] = new \Test\NamedMember();
        try {
            json_encode($t);
            $this->fail("Should have thrown an error");
        } catch (\Exception $e) {
            if (!($e->getPrevious() instanceof \KS\JsonApi\UnserializableObjectStateException)) throw $e;
            $this->assertTrue(true, "This is the desired behavior");
        }
    }

    public function testThrowsErrorOnAddMembersWithDuplicateNames() {
        $t = new IndexedCollection();
        $t['test'] = new \Test\NamedMember();
        $t['test']->setMemberName('test');

        try {
            $t['test2'] = $t['test'];
            $this->fail("Should have thrown an exception");
        } catch (\KS\JsonApi\CollectionConflictingMemberException $e) {
            $this->assertTrue(true, "This is the expected behavior");
        }
    }

    public function testThrowsErrorOnSerializeMembersWithDuplicateNames() {
        $t = new IndexedCollection();
        $t['test'] = new \Test\NamedMember();
        $t['test']->setMemberName('test');
        $t['test2'] = new \Test\NamedMember();
        $t['test2']->setMemberName('test');

        try {
            json_encode($t);
            $this->fail("Should have thrown an error");
        } catch (\Exception $e) {
            if (!($e->getPrevious() instanceof \KS\JsonApi\CollectionConflictingMemberException)) throw $e;
            $this->assertTrue(true, "This is the expected behavior");
        }
    }

    public function testSuccessfullyHandlesValidCollections() {
        $t = new IndexedCollection();
        $t['test'] = new \Test\NamedMember();
        $t['test']->setMemberName('test');
        $t['test']->setData('testData');
        $t['test2'] = new \Test\NamedMember();
        $t['test2']->setMemberName('test-two');
        $t['test2']->setData('testData2');

        $this->assertEquals('{"test":"testData","test-two":"testData2"}', json_encode($t), "Should have serialized correctly");
    }

    public function testActsLikeAnArray() {
        $t = new IndexedCollection();
        $t['test'] = new \Test\NamedMember();
        $t['test']->setMemberName('test');
        $t['test']->setData('testData');
        $t['test2'] = new \Test\NamedMember();
        $t['test2']->setMemberName('test-two');
        $t['test2']->setData('testData2');

        $this->assertEquals(2, count($t), "Should have counted correctly");

        $test = [];
        foreach($t as $k => $v) $test[$v->getMemberName()] = $v->getData();
        $this->assertEquals('{"test":"testData","test-two":"testData2"}', json_encode($test), "Should have transfered correctly to a flat array");
    }
}

