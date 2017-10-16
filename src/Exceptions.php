<?php
namespace KS\JsonApi;

class JsonApiException extends \RuntimeException { }

class CollectionConflictingMemberException extends JsonApiException { }
class CollectionUndefinedIndexException extends JsonApiException { }

class DuplicateIdException extends JsonApiException { }

/**
 * An unrecognized class of data has been passed into a constructor
 *
 * This exception allows objects to provide information about what, specifically,
 * the offending condition was. That data can be consumed further upstream to offer
 * more useful error messages in various contexts.
 */
class MalformedDataException extends JsonApiException {
    protected $offender;
    protected $offendingData;

    public function setOffender($name) {
        $this->offender = $name;
        return $this;
    }

    public function getOffender() {
        return $this->offender;
    }

    public function setOffendingData(array $data) {
        $this->offendingData = $data;
        return $this;
    }

    public function getOffendingData() {
        return $this->offendingData;
    }
}

class UninitializedRelationshipException extends JsonApiException { }
class UnknownAttributeException extends JsonApiException { }
class UnknownRelationshipException extends JsonApiException { }
class UnknownResourceTypeException extends JsonApiException {
    protected $unknownType;

    public function setUnknownType($type) {
        $this->unknownType = $type;
        return this;
    }
    public function getUnknownType() { return $this->unknownType; }
}

class UnserializableObjectStateException extends JsonApiException { }

