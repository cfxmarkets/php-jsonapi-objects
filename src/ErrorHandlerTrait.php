<?php
namespace KS\JsonApi;

trait ErrorHandlerTrait {
    use \KS\ErrorHandlerTrait;

    public function setError($field, $which=null, ErrorInterface $error) {
        if (!array_key_exists($field, $this->errors)) $this->errors[$field] = array();
        if ($which) $this->errors[$field][$which] = $error;
        else $this->errors[$field][] = $error;
        return $this;
    }
}

