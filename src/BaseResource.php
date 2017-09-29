<?php
namespace KS\JsonApi;

abstract class BaseResource implements BaseResourceInterface {
    use \KS\ErrorHandlerTrait;

    /** This resource's instance of Factory **/
    protected $f;

    /** Fields that match with JSON-API Resource fields **/
    protected $id;
    protected $resourceType;
    protected $attributes = [];
    protected $relationships = [];

    /**
     * Fields to track relationship to database.
     *
     * Generally, `$initialized` should be false when this is a new object or a resource identifier. If the resource
     * was inflated from persistence, $initialized should be true. Because resources may have complex relationships
     * wth other resources, the field `$relationshipsInitialized` may be used to track which to-many relationships
     * have been initialized. The `BaseResource` class doesn't make any accommodations for the implementation of this
     * logic, but understands that such functionality may be desireable.
     */
    protected $initialized = false;
    protected $initializedRelationships = [];


    /**
     * Constructor: constructs a Resource object
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
     *     public function setAddresses(ResourceCollectionInterface $addresses);
     *     public function addAddress(AddressInterface $address);
     *     public function hasAddress(AddressInterface $address);
     *     public function removeAddress(AddressInterface $address);
     *     public function setBoss(PersonInterface $boss);
     *
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
    }


    /**
     * Restore an object from data persisted to a secure datasource
     *
     * This just creates  new object and then marks it as initilized from the database
     *
     * @param FactoryInterface $factory A factory with which to inflate child objects
     * @param array $data An array of json-api-formatted data to inflate.
     * @return static
     */
    public static function restoreFromData(FactoryInterface $f, array $data) {
        $obj = new static($f, $data);
        $obj->initialized = true;
        return $obj;
    }


    /**
     * Update fields from passed-in user data in jsonapi format
     *
     * @param array $data A JSON-API-formatted array of data defining attributes and relationships to set
     * @return void
     */
    public function updateFromUserInput(array $data) {
        if (!array_key_exists('type', $data)) throw new \InvalidArgumentException("You must pass in a 'type' parameter, even when updating a known object.");
        if ($data['type'] != $this->resourceType) throw new \InvalidArgumentException("You've passed data for a resource that is not the same type as the one you're trying to update: `$data[type]` <> `$this->resourceType`");

        $attrs = [];
        $rels = null;
        if (array_key_exists('attributes', $data)) $attrs = $data['attributes'];
        if (array_key_exists('relationships', $data)) $rels = $data['relationships'];

        foreach($attrs as $n => $v) {
            $setAttribute = "set".ucfirst($n);
            if (!method_exists($this, $setAttribute)) throw new UnknownAttributeException("You've passed an attribute (`$n`) that is not valid for this resource.");
            $this->$setAttribute($v);
        }

        if ($rels) {
            foreach(array_keys($this->relationships) as $name) {
                // If the update doesn't concern this relationship, move on
                if (!array_key_exists($name, $rels)) continue;

                $rel = $rels[$name];
                if (!($rel instanceof RelationshipInterface)) {
                    $rel['name'] = $name;
                    $rel = $this->f->newJsonApiRelationship($rel);
                }

                $setRelationship = "set".ucfirst($name);
                if (!method_exists($this, $setRelationship)) throw new UnknownRelationshipException("You've passed a relationship (`$name`) that is not valid for this resource.");
                $this->$setRelationship($rel->getData());

                unset($rels[$name]);
            }

            if (count($rels) > 0) throw new \InvalidArgumentException("You've passed in relationships that this resource doesn't have: ".implode(', ', $rels));
        }
    }



    /**
     * Initialize the attributes array, merging passed attributes with defaults
     *
     * @param array $initialAttrs An array of initial attribute values
     * @return void
     */
    protected function initializeAttributes(array $initialAttrs=[]) {
        // Merge attributes
        $attrs = $this->attributes;
        foreach($initialAttrs as $a => $v) $attrs[$a] = $v;

        // Iterate through attributes and set
        foreach($attrs as $k => $v) {
            if (!is_string($k)) throw new \RuntimeException("You've passed an attribute with a non-string index (`$k` => `$v`). Attributes must be string-indexed, including default attributes. Example: `protected \$attributes = [ 'name' => null, 'dob' => null ]");
            $setAttribute = "set".ucfirst($k);

            // Validate
            if (!method_exists($this, $setAttribute)) throw new UnknownAttributeException("You've passed an attribute (`$k`) that is not valid for this resource.");

            // Set
            $this->$setAttribute($v);
        }
    }

    /**
     * Initialize the relationships array, merging passed relationships with defaults
     *
     * @param array $initialRels An array of initial relationships (should be in the form `'[relName]' => [relData|Relationship]`)
     * @return void
     */
    protected function initializeRelationships(array $initialRels=[]) {
        // Initialize required relationships as empty relationships
        $rels = [];
        foreach($this->relationships as $k => $r) {
            if (is_int($k)) {
                $k = $r;
                $r = [ 'data' => null ];
            }
            $rels[$k] = $r;
        }

        // Keep public relationships
        $publicRels = $rels;

        // Overwrite default required relationships with passed-in relationships
        foreach($initialRels as $n => $r) {
            // Mark passed in relationships as "initialized"
            if (!in_array($n, $this->initializedRelationships)) $this->initializedRelationships[] = $n;
            $rels[$n] = $r;
        }

        $this->relationships = [];

        // Iterate through relationships and set
        foreach($rels as $n => $r) {
            $setRelationship = "set".ucfirst($n);

            // Convert to relationship, if necessary
            if (!($r instanceof RelationshipInterface)) {
                $r['name'] = $n;
                $r = $this->f->newJsonApiRelationship($r);
            }

            // Validate
            if (!method_exists($this, $setRelationship)) throw new UnknownRelationshipException("You've passed a relationship (`$n`) that is not valid for this resource.");

            // Set
            if (array_key_exists($n, $publicRels)) $this->relationships[$n] = $r;
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

    public function getCollectionLinkPath() {
        return "/{$this->resourceType}";
    }

    public function getSelfLinkPath() {
        $path = $this->getCollectionLinkPath();
        if ($this->getId()) $path .= "/{$this->getId()}";
        return $path;
    }

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

