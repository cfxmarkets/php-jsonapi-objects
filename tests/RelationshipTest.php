<?php

class RelationshipTest extends \PHPUnit\Framework\TestCase {
    public function testRelationshipRequiresName() {
        $this->markTestIncomplete();
    }

    public function testRelationshipRejectsBadData() {
        try {
            new \CFX\JsonApi\Relationship([ 'name' => 'test', 'invalid' => 'extra!!' ]);
            $this->fail("Should have thrown exception");
        } catch (\CFX\JsonApi\MalformedDataException $e) {
            $this->assertContains("`invalid`", $e->getMessage());
            $this->assertEquals("Relationship (`test`)", $e->getOffenders()[0]);
            $this->assertEquals(['invalid'=>'extra!!'], $e->getOffendingData());
        }
    }
}

