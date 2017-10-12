<?php

class RelationshipTest extends \PHPUnit\Framework\TestCase {
    public function testRelationshipRequiresName() {
        $this->markTestIncomplete();
    }

    public function testRelationshipRejectsBadData() {
        try {
            new \KS\JsonApi\Relationship(new \KS\JsonApi\Test\Factory(), [ 'name' => 'test', 'invalid' => 'extra!!' ]);
            $this->fail("Should have thrown exception");
        } catch (\KS\JsonApi\MalformedDataException $e) {
            $this->assertContains("`invalid`", $e->getMessage());
            $this->assertEquals("Relationship (`test`)", $e->getOffender());
            $this->assertEquals(['invalid'=>'extra!!'], $e->getOffendingData());
        }
    }
}

