<?php
namespace KS\JsonApi;

class Resource implements ResourceInterface {
    use \KS\ErrorHandlerTrait;

    protected $f;

    protected $id;
    protected $type;
    protected $attributes;
    protected $validAttributes;
    protected $relationships;
    protected $validRelationships;
    protected $initilized;


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
    public function __construct(FactoryInterface $f, array $data=null, bool $initilized=true) {
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
                    $this->setRelationship($this->f->newRelationship($obj));
                }
            }
        }
        $this->initialized = $initialized;
    }

    public function setAttribute(string $attr, $val) {
        if ($this->validAttributes && !in_array($attr, $this->validAttributes)) throw new \InvalidArgumentException("Invalid attribute passed: This resource has defined a set of valid attributes which does not include `$attr`. Valid attributes are ".implode(', ', $this->validAttributes).".");
        if (!$this->attributes) $this->attributes = [];
        $this->attributes[$attr] = $val;
        $this->validateAttribute($attr);
    }

    public function setRelationship(Relationship $r) {
        if ($this->validRelationships && !in_array($r->getName(), $this->validRelationships)) throw new \InvalidArgumentException("Invalid relationship passed: This resource has defined a set of valid relationships which does not include `{$r->getName()}`. Valid relationships are ".implode(', ', $this->validRelationships).".");
        if (!$this->relationships) $this->relationships = [];
        $this->relationships[$r->getName()] = $r;
        $this->validateRelationship($r->getName());
    }





    public function getType() { return $this->type; }
    public function getId() { return $this->id; }
    public function getAttributes() {
        return $this->attributes ?: [];
    }
    public function getAttribute(string $k) {
        if (!$this->attributes) return null;
        return $this->attributes[$k];
    }
    public function getRelationships() {
        return $this->relationships ?: [];
    }
    public function getRelationship(string $k) {
        if (!$this->relationships) return null;
        return $this->relationships[$k];
    }






    public function validateRelationship(string $rel) {
    }

    public function validateAttribute(string $field) {
    }

    public function validateResource() {
    }





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

