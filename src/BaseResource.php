<?php
namespace KS\JsonApi;

abstract class BaseResource implements BaseResourceInterface {
    use \KS\ErrorHandlerTrait;

    protected $f;

    protected $id;
    protected $resourceType;
    protected $attributes = [];
    protected $relationships = [];
    protected $initialized;


    /**
     * Constructs a Resource object
     *
     * If $data is provided, it is used to set fields. If $data contains a `type` field, it cannot conflict with any
     * pre-set type or an Exception will be thrown.
     *
     * You may define the valid attributes and relationships in the `$attributes` and `relationships` arrays. Attributes
     * may have default values, though relationships may not. These are the arrays that are dumped when the object is
     * serialized to JSON, so they should represent the object's *public* attributes and relationships. Any private
     * attributes or relationships should be stored elsewhere.
     *
     * On initialization, this class merges incoming attributes and relationships with the ones given in the `$attributes`
     * and `$relationships` arrays and uses them to set values via special `set*` methods. YOU ARE RESPONSIBLE FOR
     * CREATING A `set*` METHOD FOR EACH ATTRIBUTE AND RELATIONSHIP THAT THE OBJECT CAN MANAGE, PUBLIC OR PRIVATE. If
     * you do not create these methods, an Error will be thrown.
     *
     * You should make sure you do proper validation in these `set*` methods, since this will ensure that your resource
     * is always aware of its errors.
     *
     * Given the following `$attributes` and `$relationships` arrays, set methods may look like this:
     *
     *     protected $attributes = [ 'name' => null, 'dob' => null, 'active' => true ];
     *     protected $relationships = [ 'addresses', 'boss' ];
     *
     *     public function setName($name);
     *     public function setDob($dob);
     *     public function setActive($active);
     *     public function setAddresses(AddressInterface $addresses);
     *     public function setBoss(PersonInterface $boss);
     *
     *
     * --------------------------------------------------------------------------------------------------------------
     *
     * `$initialized` is a field used to track whether or not this resource is in sync was initialized using data from
     * a database. If $initialized is true (default), then the object is assumed to be complete. If it is false, then it is
     * assumed to be a "ResourceIdentifier", i.e., an incomplete resource whose attributes and relationships may be fetched
     * from persistence later.
     */
    public function __construct(FactoryInterface $f, $data=null) {
        $this->f = $f;

        $attrs = [];
        $rels = [];
        if ($data) {
            if (array_key_exists('id', $data)) $this->id = $data['id'];
            if (array_key_exists('type', $data)) {
                if ($this->resourceType && $data['type'] != $this->resourceType) throw new \InvalidArgumentException("This Resource has a fixed type of `$this->resourceType` that cannot be altered. (You passed a type of `$data[type]`.)");
                $this->resourceType = $data['type'];
            }
            if (array_key_exists('attributes', $data)) $attrs = $data['attributes'];
            if (array_key_exists('relationships', $data)) $rels = $data['relationships'];
        }

        $this->initializeAttributes($attrs);
        $this->initializeRelationships($rels);

        $this->initialized = false;
    }

    public static function restoreFromData(FactoryInterface $f, $data) {
        $obj = new static($f, $data);
        $obj->initialized = true;
        return $obj;
    }

    protected function initializeAttributes($initialAttrs=[]) {
        // Merge attributes
        $attrs = $this->attributes;
        foreach($initialAttrs as $a => $v) $attrs[$a] = $v;

        // Iterate through attributes and set
        foreach($attrs as $k => $v) {
            if (!is_string($k)) throw new \RuntimeException("You've passed an attribute with a non-string index (`$k` => `$v`). Attributes must be string-indexed, including default attributes. Example: `protected \$attributes = [ 'name' => null, 'dob' => null ]");
            $setAttribute = "set".ucfirst($k);

            // Don't have to check for valid attributes here, because this will throw an error if there's
            // no valid setter found.
            $this->$setAttribute($v);
        }
    }

    protected function initializeRelationships($initialRels=[]) {
        // Add missing required relationships as empty relationships
        $rels = [];
        foreach($this->relationships as $r) {
            if (!is_string($r)) throw new \RuntimeException("You may only initialize relationships with an array of relationship names, since relationships may not have default values");
            $rels[$r] = [ 'data' => null ];
        }

        foreach($initialRels as $n => $r) $rels[$n] = $r;

        $this->relationships = [];

        // Iterate through relationships and set
        foreach($rels as $n => $r) {
            $setRelationship = "set".ucfirst($n);

            // Convert to relationship, if necessary
            if (!($r instanceof RelationshipInterface)) {
                $r['name'] = $n;
                $r = $this->f->newJsonApiRelationship($r);
            }

            // Set relationship (don't have to check for validity because it will throw errors if the `set[Relationship]`
            // method doesn't exist)
            $this->relationships[$n] = $r;
            $this->$setRelationship($r->getData());
        }
    }

    public function setId($id) {
        if ($this->id !== null && $id != $this->id) throw new DuplicateIdException("This resource already has an id. You cannot set a new ID for it.");
        $this->id = $id;
        return $this;
    }

    public function getResourceType() { return $this->resourceType; }
    public function getId() { return $this->id; }

    public function jsonSerialize($fullResource=true) {
        $data = [];
        $data['type'] = $this->resourceType;
        $data['id'] = $this->id;

        if (!$fullResource) return $data;

        if (count($this->attributes) > 0) $data['attributes'] = $this->attributes;
        if (count($this->relationships) > 0) {
            $data['relationships'] = [];
            foreach($this->relationships as $r) $data['relationships'][$r->getName()] = $r;
        }
        return $data;
    }
}

