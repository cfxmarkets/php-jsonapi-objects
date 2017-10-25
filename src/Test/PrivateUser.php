<?php
namespace CFX\JsonApi\Test;

class PrivateUser extends User {
    public function setId($id) {
        if ($this->getId() !== null) throw new \RuntimeException("Programmer: Can't set ID more than once.");
        $this->id = $id;
    }

    public function setReadOnly($val) {
        $this->_setAttribute('readonly', $val);
    }
}

