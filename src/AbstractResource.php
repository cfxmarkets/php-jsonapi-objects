<?php
namespace KS\JsonApi;

abstract class AbstractResource implements ResourceInterface {
    use \KS\ErrorHandlerTrait;

    /** This resource's instance of the Context **/
    protected $context;

    /** Fields that match with JSON-API Resource fields **/
    protected $id;
    protected $resourceType;
    protected $attributes = [];
    protected $relationships = [];

    private $changedAttributes = [];
    private $changedRelationships = [];
    private $trackChanges = true;

    /**
     * Fields to track relationship to database.
     *
     * Generally, `$initialized` should be false when this is a new object or a resource identifier. If the resource
     * was inflated from persistence, $initialized should be true. Because resources may have complex relationships
     * wth other resources, the field `$relationshipsInitialized` may be used to track which to-many relationships
     * have been initialized. The `AbstractResource` class doesn't make any accommodations for the implementation of this
     * logic, but understands that such functionality may be desireable.
     */
    private $initialized = false;
    private $initializedRelationships = [];


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
     * @param FactoryInterface $context A factory with which to instantiate child objects
     * @param array $data An optional array of user data with which to initialize the object
     * @return static
     */
    public function __construct(ContextInterface $context, $data=null) {
        $this->context = $context;

        // Set all attributes to null initially, saving default values
        $defaultAttrVals = [];
        foreach($this->attributes as $attr => $v) {
            $defaultAttrVals[$attr] = $v;
            $this->attributes[$attr] = null;
        }

        // Set default values using setters (to trigger validation and change tracking)
        foreach($defaultAttrVals as $attr => $v) {
            $setAttribute = "set".ucfirst($attr);
            $this->$setAttribute($v);
        }

        // Set relationships
        $relationships = [];
        foreach($this->relationships as $name) $relationships[$name] = $this->context->newJsonApiRelationship(['name' => $name]);
        $this->relationships = $relationships;

        // Check to see if there's data waiting for us in our database
        $this->restoreFromData();

        // If we've passed in initial data, update from that
        if ($data) $this->updateFromJsonApi($data);
    }


    /**
     * Restore an object from data persisted to a secure context
     *
     * This method is called from the constructor ONLY and is intended to allow the context 
     */
    protected function restoreFromData() {
        $data = $this->context->getCurrentData();
        if ($data) {
            $this->trackChanges = false;
            $this->updateFromJsonApi($data);
            $this->trackChanges = true;
            $this->initialized = true;
        }
    }


    /**
     * Update fields from passed-in user data in jsonapi format
     *
     * @param array $data A JSON-API-formatted array of data defining attributes and relationships to set
     * @return void
     */
    public function updateFromJsonApi(array $data) {
        // Set ID
        if (array_key_exists('id', $data)) {
            $this->setId($data['id']);
            unset($data['id']);
        }

        // Set Type
        if (array_key_exists('type', $data)) {
            if ($this->resourceType && $data['type'] != $this->resourceType) throw new \InvalidArgumentException("This Resource has a fixed type of `$this->resourceType` that cannot be altered. (You passed a type of `$data[type]`.)");
            $this->resourceType = $data['type'];
            unset($data['type']);
        }

        // Set Attributes
        if (array_key_exists('attributes', $data)) {
            foreach($data['attributes'] as $n => $v) {
                $setAttribute = "set".ucfirst($n);
                if (!method_exists($this, $setAttribute)) throw new UnknownAttributeException("You've passed an attribute (`$n`) that is not valid for this resource.");
                $this->$setAttribute($v);
            }
            unset($data['attributes']);
        }

        // Set Relationships
        if (array_key_exists('relationships', $data)) {
            foreach($data['relationships'] as $name => $rel) {
                if (!($rel instanceof RelationshipInterface)) {
                    $rel['name'] = $name;
                    $rel = $this->context->newJsonApiRelationship($rel);
                }

                // Validate that the relationship is settable
                $setRelationship = "set".ucfirst($name);
                if (!method_exists($this, $setRelationship)) throw new UnknownRelationshipException("You've passed a relationship (`$name`) that is not valid for this resource.");

                // Finally, set the relationship through it's setter
                $this->$setRelationship($rel->getData());
            }
            unset($data['relationships']);
        }

        // Now throw errors on leftover data
        if (count($data) > 0) {
            $e = new MalformedDataException("You have unrecognized data in your JsonApi Resource. Offending keys are: `".implode('`, `', array_keys($data))."`.");
            $e->setOffender("Resource (`$this->resourceType`)");
            $e->setOffendingData($data);
            throw $e;
        }
    }

    /**
     * setId
     *
     * Set the Id, if it's not already set
     *
     * @param string $id The id of the object
     * @return static
     *
     * @throws DuplicateIdException
     */
    public function setId($id) {
        if ($this->id !== null && $id != $this->id) throw new DuplicateIdException("This resource already has an id. You cannot set a new ID for it.");
        $this->id = $id;
        return $this;
    }

    /**
     * getResourceType
     *
     * @return string $resourceType
     */
    public function getResourceType() { return $this->resourceType; }

    /**
     * getId
     *
     * @return string $id
     */
    public function getId() { return $this->id; }

    /**
     * getCollectionLinkPath
     *
     * @return string The path for the collection
     */
    public function getCollectionLinkPath() {
        return "/{$this->resourceType}";
    }

    /**
     * getSelfLinkPath
     *
     * @return string
     */
    public function getSelfLinkPath() {
        $path = $this->getCollectionLinkPath();
        if ($this->getId()) $path .= "/{$this->getId()}";
        return $path;
    }

    /**
     * jsonSerialize
     *
     * Serialize to JsonApi-formatted json
     *
     * @param bool $fullResource Whether or not to serialize attributes and relationships, too. (Set to false by default for relationships.)
     */
    public function jsonSerialize($fullResource=true) {
        $data = [];
        $data['type'] = $this->resourceType;
        $data['id'] = $this->id;

        if (!$fullResource) return $data;

        if (count($this->attributes) > 0) {
            foreach($this->attributes as $name => $v) $data['attributes'][$name] = $this->serializeAttribute($name);
        }
        if (count($this->relationships) > 0) {
            $data['relationships'] = [];
            foreach($this->relationships as $r) $data['relationships'][$r->getName()] = $r;
        }
        return $data;
    }

    /**
     * serializeAttribute
     *
     * Serializes the value of the given attribute, if necessary
     *
     * @param string $name The name of the attribute
     * @return mixed $value
     */
    protected function serializeAttribute($name) {
        return $this->attributes[$name];
    }

    /**
     * setAttribute
     *
     * Set an attribute, tracking changes (if applicable)
     *
     * @param string $name The name of the attribute to set
     * @param mixed $value The value to set it to
     */
    protected function _setAttribute($name, $val) {
        if ($val == $this->attributes[$name]) return $this;
        $this->attributes[$name] = $val;
        if ($this->trackChanges) $this->changedAttributes[$name] = $val;
        return $this;
    }

    /**
     * setRelationship
     *
     * Set a relationship, tracking changes (if applicable)
     *
     * @param string $name The name of the relationship to set
     * @param ResourceInterface|ResourceCollectionInterface $value The value to set it to
     */
    protected function _setRelationship($name, $val) {
        $changed = true;
        if (!$val) {
            if ($this->relationships[$name]->getData() === null) $changed = false;
        } elseif ($val instanceof ResourceInterface) {
            $rel = $this->relationships[$name]->getData();
            if ($rel && $val->getId() == $rel->getId()) $changed = false;
        } elseif ($val instanceof ResourceCollectionInterface) {
            $newResources = $currentResources = [];
            foreach ($val as $k => $resource) $newResources[] = $resource->getId();
            if ($this->relationships[$name]->getData()) {
                foreach ($this->relationships[$name]->getData() as $k => $resource) $currentResources[] = $resource->getId();
            }
            sort($newResources);
            sort($currentResources);
            if (implode('', $newResources) == implode('', $currentResources)) $changed = false;
        } else {
            var_dump($val);
            throw new \RuntimeException("Unrecognized relationship type! Relationships should be either Resources or ResourceCollections.");
        }

        $this->relationships[$name]->setData($val);
        if ($changed && $this->trackChanges) $this->changedRelationships[$name] = $val;

        return $this;
    }
}

