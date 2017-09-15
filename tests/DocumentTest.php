<?php

use \KS\JsonApi\Document;
use \KS\JsonApi\DocumentInterface;
use \KS\JsonApi\GenericResource;
use \KS\JsonApi\GenericResourceInterface;
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
use \Test\TestData;
use \Test\Factory;

class DocumentTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateBlankDoc() {
        $doc = new Document(Factory::getInstance());
        $this->assertTrue($doc instanceof \KS\JsonApi\DocumentInterface, "Correct: Shouldn't have thrown an error");
    }

    public function testDocumentInterface() {
        $doc = new Document(Factory::getInstance());
        $doc->setData(new ResourceCollection([
            new GenericResource(Factory::getInstance(), [
                'type' => 'test',
                'id' => '1',
                'attributes' => [
                    'test1' => 1
                ]
            ]),
        ]));

        $doc->setData(new GenericResource(Factory::getInstance(), [
            'type' => 'test',
            'id' => '1',
            'attributes' => [
                'test1' => 1
            ]
        ]));

        $doc->addLink(new Link(Factory::getInstance(), [
            'name' => 'self',
            'href' => '/test/link',
        ]));

        $doc->setMeta(new Meta([
            'item1' => 1,
            'item2' => 2,
        ]));

        $this->assertTrue($doc->getData() instanceof GenericResourceInterface);
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

        $doc->addError(new Error(Factory::getInstance(), [
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
}

