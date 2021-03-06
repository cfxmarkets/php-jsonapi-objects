<?php
namespace CFX\JsonApi;

class FullIntegrationTest extends \PHPUnit\Framework\TestCase {
    public function testCorrectlyUnserializesJsonApiDoc() {
        $this->markTestIncomplete();
        $struct = Test\TestData::get('data');
        $struct['links'] = Test\TestData::get('links')['links'];
        $doc = new Document($struct);

        $this->assertTrue($doc->getData() instanceof ResourceCollection);
        $this->assertTrue($doc->getData()[0] instanceof ResourceInterface);

        for($i = 0; $i < 2; $i++) {
            $this->assertEquals('test1', $doc->getData()[$i]->getResourceType());
            $this->assertEquals($struct['data'][$i]['id'], $doc->getData()[$i]->getId());
            $this->assertEquals(count($struct['data'][$i]['attributes']), count($doc->getData()[$i]->getAttributes()));
            $this->assertEquals($struct['data'][$i]['attributes']['city'], $doc->getData()[$i]->getAttribute('city'));
            $this->assertEquals(count($struct['data'][$i]['relationships']), count($doc->getData()[$i]->getRelationships()));
            $this->assertEquals(array_keys($struct['data'][$i]['relationships'])[0], $doc->getData()[$i]->getRelationship('owner')->getName());
            if ($i == 0) {
                $this->assertTrue($doc->getData()[$i]->getRelationship('owner')->getData() instanceof ResourceInterface);
                $this->assertEquals($struct['data'][$i]['relationships']['owner']['data']['id'], $doc->getData()[$i]->getRelationship('owner')->getData()->getId());
                $this->assertTrue($doc->getData()[$i]->getRelationship('inhabitants')->getData() instanceof ResourceCollection);
                $this->assertTrue($doc->getData()[$i]->getRelationship('inhabitants')->getData()[0] instanceof ResourceInterface);
            } else {
                $this->assertNull($doc->getData()[$i]->getRelationship('owner')->getData());
                $this->assertEquals(0, count($doc->getData()[$i]->getRelationship('inhabitants')->getData()));
            }

            $this->assertTrue($doc->getLinks() instanceof LinksCollectionInterface);
            $this->assertEquals('/test/link',$doc->getLinks()['self']->getHref());
        }
    }

    public function testCorrectlyReserializesJsonApiDoc() {
        $this->markTestIncomplete();
        $struct = Test\TestData::get('data');
        $struct['links'] = Test\TestData::get('links')['links'];
        $struct['jsonapi'] = [ 'version' => '1.0' ];
        $doc = new Document($struct);
        $this->assertEquals(json_encode($struct), json_encode($doc));
    }

    public function testCorrectlyHandlesErrors() {
        $e = Test\TestData::get('errors');
        $doc = new Document($e);
        $this->assertEquals($e['errors'][0]['status'], $doc->getErrors()[0]->getStatus());
    }
}
