<?php

use \KS\JsonApi\Document;
use \KS\JsonApi\DocumentInterface;
use \KS\JsonApi\Test\User;
use \KS\JsonApi\ResourceInterface;
use \KS\JsonApi\ResourceCollection;
use \KS\JsonApi\ResourceCollectionInterface;
use \KS\JsonApi\Error;
use \KS\JsonApi\ErrorInterface;
use \KS\JsonApi\ErrorsCollectionInterface;
use \KS\JsonApi\Meta;
use \KS\JsonApi\MetaInterface;
use \KS\JsonApi\Link;
use \KS\JsonApi\LinkInterface;
use \KS\JsonApi\LinksCollectionInterface;
use \KS\JsonApi\Test\Context;

class DocumentTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateBlankDoc() {
        $doc = new Document(new Context());
        $this->assertTrue($doc instanceof \KS\JsonApi\DocumentInterface, "Correct: Shouldn't have thrown an error");
    }

    public function testDocumentInterface() {
        $doc = new Document(new Context());
        $doc->setData(new ResourceCollection([
            new User(new Context(), [
                'type' => 'test-users',
                'id' => '1',
                'attributes' => [
                    'name' => 'joni'
                ]
            ]),
        ]));

        $doc->setData(new User(new Context(), [
            'type' => 'test-users',
            'id' => '1',
            'attributes' => [
                'name' => 'joni'
            ]
        ]));

        $doc->addLink(new Link(new Context(), [
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

        $doc->addError(new Error(new Context(), [
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
            new \KS\JsonApi\Document(new Context(), ['something' => 'invalid']);
            $this->fail("Should have thrown an exception");
        } catch (\KS\JsonApi\MalformedDataException $e) {
            $this->assertContains("`something`", $e->getMessage());
            $this->assertEquals("Document", $e->getOffender());
            $this->assertEquals(['something'=>'invalid'], $e->getOffendingData());
        }
    }
}

