<?php

use \KS\JsonApi as japi;
use \Test\TestData;

class FullIntegrationTest extends \PHPUnit\Framework\TestCase {
    public function testCorrectlyUnserializesJsonApiDoc() {
        $struct = TestData::get('data');
        $struct['links'] = TestData::get('links')['links'];
        $doc = new japi\Document($this->f(), $struct);

        $this->assertTrue($doc->getData() instanceof japi\ResourceCollection);
        $this->assertTrue($doc->getData()[0] instanceof japi\BaseResourceInterface);

        for($i = 0; $i < 2; $i++) {
            $this->assertEquals('test1', $doc->getData()[$i]->getResourceType());
            $this->assertEquals($struct['data'][$i]['id'], $doc->getData()[$i]->getId());
            $this->assertEquals(count($struct['data'][$i]['attributes']), count($doc->getData()[$i]->getAttributes()));
            $this->assertEquals($struct['data'][$i]['attributes']['city'], $doc->getData()[$i]->getAttribute('city'));
            $this->assertEquals(count($struct['data'][$i]['relationships']), count($doc->getData()[$i]->getRelationships()));
            $this->assertEquals(array_keys($struct['data'][$i]['relationships'])[0], $doc->getData()[$i]->getRelationship('owner')->getName());
            if ($i == 0) {
                $this->assertTrue($doc->getData()[$i]->getRelationship('owner')->getData() instanceof japi\BaseResourceInterface);
                $this->assertEquals($struct['data'][$i]['relationships']['owner']['data']['id'], $doc->getData()[$i]->getRelationship('owner')->getData()->getId());
                $this->assertTrue($doc->getData()[$i]->getRelationship('inhabitants')->getData() instanceof japi\ResourceCollection);
                $this->assertTrue($doc->getData()[$i]->getRelationship('inhabitants')->getData()[0] instanceof japi\BaseResourceInterface);
            } else {
                $this->assertNull($doc->getData()[$i]->getRelationship('owner')->getData());
                $this->assertEquals(0, count($doc->getData()[$i]->getRelationship('inhabitants')->getData()));
            }

            $this->assertTrue($doc->getLinks() instanceof japi\LinksCollectionInterface);
            $this->assertEquals('/test/link',$doc->getLinks()['self']->getHref());
        }
    }

    public function testCorrectlyReserializesJsonApiDoc() {
        $struct = TestData::get('data');
        $struct['links'] = TestData::get('links')['links'];
        $struct['jsonapi'] = [ 'version' => '1.0' ];
        $doc = new japi\Document($this->f(), $struct);
        $this->assertEquals(json_encode($struct), json_encode($doc));
    }

    public function testCorrectlyHandlesErrors() {
        $e = TestData::get('errors');
        $doc = new japi\Document($this->f(), $e);
        $this->assertEquals($e['errors'][0]['status'], $doc->getErrors()[0]->getStatus());
    }



    protected function f() { return \Test\Factory::getInstance(); }
}
