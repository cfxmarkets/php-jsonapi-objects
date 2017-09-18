<?php

use \KS\JsonApi\GenericResource;
use \Test\Factory;

class ResourceTest extends \PHPUnit\Framework\TestCase {
    public function testCanCreateEmptyResource() {
        $f = new Factory();
        $t = new GenericResource($f);
        $this->assertTrue($t instanceof \KS\JsonApi\GenericResourceInterface, "Should instantiate a valid GenericResource object");
    }

    public function testCanCreateValidResource() {
        $data = [
            'type' => 'test-users',
            'attributes' => [
                'name' => 'Jim Chavo',
                'dob' => '12345',
            ],
            'relationships' => [
                'friends' => [
                    'data' => [
                        [
                            'type' => 'test-users',
                            'id' => '1',
                        ],
                        [
                            'type' => 'test-users',
                            'id' => '2',
                        ],
                    ],
                ],
                'boss' => [
                    'data' => [
                        'type' => 'test-users',
                        'id' => '3',
                    ],
                ],
            ],
        ];

        $f = new Factory();

        $t = new \Test\User($f, $data);

        $this->assertTrue($t instanceof \KS\JsonApi\BaseResourceInterface);
        $this->assertTrue($t->getFriendsRelationship() instanceof \KS\JsonApi\RelationshipInterface);
        $this->assertTrue($t->getBossRelationship() instanceof \KS\JsonApi\RelationshipInterface);
        $this->assertTrue($t->getFriends() instanceof \KS\JsonApi\ResourceCollectionInterface);
        $this->assertTrue($t->getBoss() instanceof \KS\JsonApi\BaseResourceInterface);
        $this->assertEquals('Jim Chavo', $t->getName());
        $this->assertEquals('12345', $t->getDob());
    }

    public function testThrowsErrorOnInvalidAttribute() {
        $data = [
            'type' => 'test-users',
            'attributes' => [
                'name' => 'Jim Chavo',
                'dob' => '12345',
                'invalidAttr' => 'nope',
            ],
        ];

        $f = new Factory();

        try {
            $t = new \Test\User($f, $data);
            $this->fail("Should have thrown an exception");
        } catch (\Error $e) {
            $this->assertContains("undefined method", $e->getMessage());
        }
    }

    public function testThrowsErrorOnInvalidRelationship() {
        $data = [
            'type' => 'test-users',
            'attributes' => [
                'name' => 'Jim Chavo',
                'dob' => '12345',
            ],
            'relationships' => [
                'friends' => [
                    'data' => [
                        [
                            'type' => 'test-users',
                            'id' => '1',
                        ],
                    ],
                ],
                'boss' => [
                    'data' => [
                        'type' => 'test-users',
                        'id' => '3',
                    ],
                ],
                'invalidRel' => [
                    'data' => [
                        'type' => 'test-users',
                        'id' => '2',
                    ],
                ]
            ],
        ];

        $f = new Factory();

        try {
            $t = new \Test\User($f, $data);
            $this->fail("Should have thrown an exception");
        } catch (\Error $e) {
            $this->assertContains("undefined method", $e->getMessage());
        }
    }

    public function testSerializesCorrectly() {
        $data = [
            'type' => 'test-users',
            'attributes' => [
                'name' => 'Jim Chavo',
                'dob' => '12345',
            ],
            'relationships' => [
                'friends' => [
                    'data' => [
                        [
                            'type' => 'test-users',
                            'id' => '1',
                        ],
                        [
                            'type' => 'test-users',
                            'id' => '2',
                        ],
                    ],
                ],
                'boss' => [
                    'data' => [
                        'type' => 'test-users',
                        'id' => '3',
                    ],
                ],
            ],
        ];

        $f = new Factory();

        $t = new \Test\User($f, $data);

        $data = [
            'type' => $data['type'],
            'id' => null,
            'attributes' => $data['attributes'],
            'relationships' => $data['relationships'],
        ];

        $this->assertEquals(json_encode($data), json_encode($t), "Should have serialized back to the original input structure");
    }
}

