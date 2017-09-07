<?php
namespace Test;

class TestErrorHandler {
    use \KS\ErrorHandlerTrait;

    public function produceError($field, $errorType=null, \KS\JsonApi\ErrorInterface $error, $new=false) {
        if ($new) $this->clearError($field);
        $this->setError($field, $errorType, $error);
    }

    public function deleteError($field, $which=null) {
        $this->clearError($field, $which);
    }

    public function deleteAllErrors() {
        $this->clearAllErrors();
    }
}

