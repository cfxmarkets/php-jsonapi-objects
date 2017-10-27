<?php
namespace CFX\JsonApi;

trait PrivateResourceTrait {
    public function setId($id) {
        $this->honorReadOnly = false;
        parent::setId($id);
        $this->honorReadOnly = true;
    }
}

