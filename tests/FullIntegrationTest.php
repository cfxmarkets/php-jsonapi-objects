<?php

use \KS\JsonApi as japi;
use \Test\TestData;

class FullIntegrationTest extends \PHPUnit\Framework\TestCase {
    public function testCorrectlyUnserializesJsonApiDoc() {
        $struct = TestData::get('data');
        $doc = new japi\Document($struct);

        $this->assertTrue($doc->getData() instanceof japi\ResourceCollection);
        $this->assertTrue($doc->getData()[0] instanceof japi\Resource);

        for($i = 0; $i < 2; $i++) {
            $this->assertEquals('test1', $doc->getData()[$i]->getType());
            $this->assertEquals($struct['data'][$i]['id'], $doc->getData()[$i]->getId());
            $this->assertEquals(count($struct['data'][$i]['attributes']), count($doc->getData()[$i]->getAttributes()));
            $this->assertEquals($struct['data'][$i]['attributes']['city'], $doc->getData()[$i]->getAttribute('city'));
            $this->assertEquals(count($struct['data'][$i]['relationships']), count($doc->getData()[$i]->getRelationships()));
            $this->assertEquals(array_keys($struct['data'][$i]['relationships'])[0], $doc->getData()[$i]->getRelationship('owner')->getName());
            if ($i == 0) {
                $this->assertTrue($doc->getData()[$i]->getRelationship('owner')->getData() instanceof japi\Resource);
                $this->assertEquals($struct['data'][$i]['relationships']['owner']['data']['id'], $doc->getData()[$i]->getRelationship('owner')->getData()->getId());
                $this->assertTrue($doc->getData()[$i]->getRelationship('inhabitants')->getData() instanceof japi\ResourceCollection);
                $this->assertTrue($doc->getData()[$i]->getRelationship('inhabitants')->getData()[0] instanceof japi\Resource);
            } else {
                $this->assertNull($doc->getData()[$i]->getRelationship('owner')->getData());
                $this->assertEquals(0, count($doc->getData()[$i]->getRelationship('inhabitants')->getData()));
            }
        }
    }

    public function testCorrectlyReserializesJsonApiDoc() {
        $struct = TestData::get('data');
        $doc = new japi\Document($struct);
        $this->assertEquals(json_encode($struct), json_encode($doc));
    }

    public function testCorrectlyHandlesErrors() {
        $e = TestData::get('errors');
        $doc = new japi\Document($e);
        $this->assertEquals($e['errors'][0]['status'], $doc->getErrors()[0]->getStatus());
    }
}
