<?php
namespace KS\JsonApi;

class BaseResource implements BaseResourceInterface {
    use \KS\ErrorHandlerTrait;

    protected $f;

    protected $id;
    protected $type;
    protected $attributes;
    protected $validAttributes = [];
    protected $relationships;
    protected $validRelationships = [];
    protected $initialized;


    /**
     * Constructs a Resource object
     *
     * If $data is provided, it is used to set fields. If $data contains a `type` field, it cannot conflict with any
     * pre-set type or an Exception will be thrown. Similarly, any passed Relationships or Attributes cannot conflict
     * with any pre-set values (i.e., you can't pass an arbitrary relationship if the given class has defined
     * that it only accepts certain types of relationships).
     *
     * If $initialized is true (default), then the object is assumed to be complete. If it is false, then it assumed
     * to be a "ResourceIdentifier", i.e., an incomplete resource whose attributes and relationships may be fetched
     * from persistence.
     */
    public function __construct(FactoryInterface $f, $data=null) {
        $this->f = $f;

        if ($data) {
            if (array_key_exists('id', $data)) $this->id = $data['id'];
            if (array_key_exists('type', $data)) {
                if ($this->type && $data['type'] != $this->type) throw new \InvalidArgumentException("This Resource has a fixed type of `$this->type` that cannot be altered. (You passed a type of `$data[type]`.)");
                $this->type = $data['type'];
            }
            if (array_key_exists('attributes', $data)) {
                foreach($data['attributes'] as $attr => $v) $this->setAttribute($attr, $v);
            }
            if (array_key_exists('relationships', $data) && count($data['relationships']) > 0) {
                foreach($data['relationships'] as $rel => $obj) {
                    $obj['name'] = $rel;
                    $this->setRelationship($this->f->newJsonApiRelationship($obj));
                }
            }
        }

        if (is_array($this->validAttributes)) {
            foreach($this->validAttributes as $attr) {
                if (!$this->getAttribute($attr)) $this->setAttribute($attr, null);
            }
        }

        if (is_array($this->validRelationships)) {
            foreach($this->validRelationships as $rel) {
                // `getRelationship` automatically creates an empty relationship for the given key if one doesn't exist
                $this->getRelationship($rel);
                $this->validateRelationship($rel);
            }
        }
    }

    public static function restoreFromData(FactoryInterface $f, $data) {
        $obj = new static($f, $data, true);
        $obj->initialized = true;
        return $obj;
    }

    public function setId($id) {
        if ($this->id !== null && $id != $this->id) throw new DuplicateIdException("This resource already has an id. You cannot set a new ID for it.");
        $this->id = $id;
        return $this;
    }

    public function getResourceType() { return $this->type; }
    public function getId() { return $this->id; }

    public function jsonSerialize($fullResource=true) {
        $data = [];
        $data['type'] = $this->type;
        $data['id'] = $this->id;

        if (!$fullResource) return $data;

        if ($this->attributes) $data['attributes'] = $this->attributes;
        if ($this->relationships) {
            $data['relationships'] = [];
            foreach($this->relationships as $r) $data['relationships'][$r->getName()] = $r;
        }
        return $data;
    }
}

