<?php
namespace CFX\JsonApi\Test;

class User extends \CFX\JsonApi\AbstractResource {
    use \CFX\JsonApi\Rel2MTrait;

    protected $resourceType = 'test-users';
    protected $attributes = [ 'name' => null, 'dob' => null, 'readonly' => 'default value' ];
    protected $relationships = [ 'friends' => null, 'boss' => null ];

    public function setName($val) {
        $this->_setAttribute('name', $val);
    }

    public function setDob($val) {
        $this->_setAttribute('dob', $val);
    }

    public function setReadOnly($val) {
        if ($this->validateReadOnly('readonly', $val != $this->getReadOnly())) {
            $this->_setAttribute('readonly', $val);
        }
    }

    public function setFriends(\CFX\JsonApi\ResourceCollectionInterface $r=null) {
        $this->_setRelationship('friends', $r);
    }

    public function setBoss(\CFX\JsonApi\ResourceInterface $r=null) {
        $this->_setRelationship('boss', $r);
    }

    public function getName() { return $this->attributes['name']; }
    public function getDob() { return $this->attributes['dob']; }
    public function getReadOnly() { return $this->attributes['readonly']; }
    public function getFriends() { return $this->relationships['friends']->getData(); }
    public function getBoss() { return $this->relationships['boss']->getData(); }
    public function getFriendsRelationship() { return $this->relationships['friends']; }
    public function getBossRelationship() { return $this->relationships['boss']; }

}


