<?php

use \CFX\JsonApi\Document;
use \CFX\JsonApi\DocumentInterface;
use \CFX\JsonApi\Test\User;
use \CFX\JsonApi\ResourceInterface;
use \CFX\JsonApi\ResourceCollection;
use \CFX\JsonApi\ResourceCollectionInterface;
use \CFX\JsonApi\Error;
use \CFX\JsonApi\ErrorInterface;
use \CFX\JsonApi\ErrorsCollectionInterface;
use \CFX\JsonApi\Meta;
use \CFX\JsonApi\MetaInterface;
use \CFX\JsonApi\Link;
use \CFX\JsonApi\LinkInterface;
use \CFX\JsonApi\LinksCollectionInterface;
use \CFX\JsonApi\Test\Context;

class DocumentTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateBlankDoc() {
        $doc = new Document();
        $this->assertTrue($doc instanceof \CFX\JsonApi\DocumentInterface, "Correct: Shouldn't have thrown an error");
    }

    public function testDocumentInterface() {
        $doc = new Document();
        $users = new \CFX\JsonApi\Test\UsersDatasource();

        $doc->setData(new ResourceCollection([
            $users->create([
                'type' => 'test-users',
                'attributes' => [
                    'name' => 'joni'
                ]
            ]),
        ]));

        $this->assertTrue($doc->getData() instanceof ResourceCollectionInterface);

        $doc->setData($users->create([
            'type' => 'test-users',
            'attributes' => [
                'name' => 'joni'
            ]
        ]));

        $doc->addLink(new Link([
            'name' => 'self',
            'href' => '/test/link',
        ]));

        $doc->setMeta(new Meta([
            'item1' => 1,
            'item2' => 2,
        ]));

        $this->assertTrue($doc->getData() instanceof ResourceInterface);
        $this->assertTrue($doc->getErrors() instanceof ErrorsCollectionInterface);
        $this->assertTrue($doc->getLinks() instanceof LinksCollectionInterface);
        $this->assertTrue($doc->getLink('self') instanceof LinkInterface);
        $this->assertTrue($doc->getMeta() instanceof MetaInterface);

        $struct = [
            'data' => $doc->getData(),
            'links' => $doc->getLinks(),
            'meta' => $doc->getMeta(),
            'jsonapi' => [ 'version' => '1.0' ],
        ];

        $this->assertEquals(json_encode($struct), json_encode($doc));

        $doc->addError(new Error([
            'status' => 400,
            'title' => 'Invalid Data',
            'detail' => 'Malformed entry',
        ]));

        $struct = [
            'errors' => $doc->getErrors(),
            'links' => $doc->getLinks(),
            'meta' => $doc->getMeta(),
            'jsonapi' => [ 'version' => '1.0' ],
        ];

        $this->assertEquals(json_encode($struct), json_encode($doc));
    }


    public function testDocumentRejectsMalformedData() {
        try {
            new \CFX\JsonApi\Document(['something' => 'invalid']);
            $this->fail("Should have thrown an exception");
        } catch (\CFX\JsonApi\MalformedDataException $e) {
            $this->assertContains("`something`", $e->getMessage());
            $this->assertEquals("Document", $e->getOffenders()[0]);
            $this->assertEquals(['something'=>'invalid'], $e->getOffendingData());
        }
    }
}

