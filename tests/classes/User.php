<?php
namespace Test;

class User extends \KS\JsonApi\BaseResource {
    protected $resourceType = 'test-users';
    protected $attributes = [ 'name' => null, 'dob' => null ];
    protected $relationships = [ 'friends', 'boss' ];

    public function setName($val) {
        $this->attributes['name'] = $val;
    }

    public function setDob($val) {
        $this->attributes['dob'] = $val;
    }

    public function setFriends(\KS\JsonApi\RelationshipInterface $r) {
        $this->relationships[$r->getName()] = $r;
    }

    public function setBoss(\KS\JsonApi\RelationshipInterface $r) {
        $this->relationships[$r->getName()] = $r;
    }

    public function getName() { return $this->attributes['name']; }
    public function getDob() { return $this->attributes['dob']; }
    public function getFriends() { return $this->relationships['friends']->getData(); }
    public function getBoss() { return $this->relationships['boss']->getData(); }
    public function getFriendsRelationship() { return $this->relationships['friends']; }
    public function getBossRelationship() { return $this->relationships['boss']; }
}


