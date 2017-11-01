<?php
namespace CFX\JsonApi;

abstract class AbstractResource implements ResourceInterface {
    use \KS\ErrorHandlerTrait;

    /** This resource's instance of the Datasource. **/
    protected $datasource;

    /** This resource's factory instance for instantiating other jsonapi family members **/
    protected $factory;

    /** Fields that match with JSON-API Resource fields **/
    protected $id;
    protected $resourceType;
    protected $attributes = [];
    protected $relationships = [];

    /** Flag for honoring read-only attributes and relationships **/
    private $honorReadOnly = true;

    /** Change-tracking properties **/
    private $initialState = [ 'attributes' => [], 'relationships' => [] ];
    private $changes = [ 'attributes' => [], 'relationships' => [] ];
    protected $trackChanges = true;

    /**
     * Fields to track relationship to database.
     *
     * Generally, `$initialized` should be false when this is a new object or a resource identifier. If the resource
     * was inflated from persistence, $initialized should be true. Because resources may have complex relationships
     * wth other resources, the field `$relationshipsInitialized` may be used to track which to-many relationships
     * have been initialized. The `AbstractResource` class doesn't make any accommodations for the implementation of this
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
     * You may define the valid attributes and relationships in the `$attributes` and `relationships` arrays. Either may have
     * default values (if it makes sense).. These are the arrays that are dumped when the object is
     * serialized to JSON, so they should represent the object's *public* attributes and relationships. Any private
     * attributes or relationships should be stored elsewhere.
     *
     * On initialization, this class merges incoming attributes and relationships with the ones given in the `$attributes`
     * and `$relationships` arrays and uses them to set values via special `set*` methods. YOU ARE RESPONSIBLE FOR
     * CREATING A `set*` METHOD FOR EACH ATTRIBUTE AND RELATIONSHIP THAT THE OBJECT CAN MANAGE, PUBLIC OR PRIVATE. If
     * you do not create these methods, an exception will be thrown.
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
     * @param array $data An optional array of user data with which to initialize the object
     * @return static
     */
    public function __construct(DatasourceInterface $datasource, $data=null) {
        $this->datasource = $datasource;

        $defaultData = [
            'attributes' => [],
            'relationships' => [],
        ];

        // Set all attributes to null initially, saving default values
        foreach($this->attributes as $attr => $v) {
            if (is_int($attr)) throw new \RuntimeException("Programmer: You must define all attributes and relationships as key-value pairs, e.g., `protected \$attributes = [ 'name' => null, 'active' => true ];`. Offending attribute: `$attr: $v` in `".get_class($this).".");

            $defaultData['attributes'][$attr] = $v;
            $this->attributes[$attr] = null;
        }

        // Set relationships to null relationships, saving defaults
        foreach($this->relationships as $name => $v) {
            if (is_int($name)) throw new \RuntimeException("Programmer: You must define all attributes and relationships as key-value pairs, e.g., `protected \$relationships = [ 'friends' => [ 'data' => null ] ];`. Offending relationship: `$name: $v` in `".get_class($this).".");

            $defaultData['relationships'][$name] = $v;
            $this->relationships[$name] = $this->getFactory()->newRelationship(['name' => $name]);
        }

        // Set initial state here so it doesn't break down the line
        $this->setInitialState();

        // update from data with default values to trigger validation and change tracking.
        try {
            $this->internalUpdateFromData($defaultData);
        } catch (UnknownAttributeException $e) {
            throw new \RuntimeException("Programmer: Looks like you may have forgotten to add a setter for attribute `{$e->getOffenders()[0]}` in `".get_class($this).". All attributes should have setters, though these setters don't have to be in the public scope.");
        } catch (UnknownRelationshipException $e) {
            throw new \RuntimeException("Programmer: Looks like you may have forgotten to add a setter for relationship `{$e->getOffenders()[0]}` in `".get_class($this).". All relationships should have setters, though these setters don't have to be in the public scope. (Relationships setters should set the *data* for the relationship, that is, they should receive a ResourceInterface, a ResourceCollectionInterface, or null.)");
        }

        // Check to see if there's data waiting for us in our database
        $this->restoreFromData();

        // Now set initial state again after full data initialization
        $this->setInitialState();

        // Finally, if we've passed in initial data, update from that
        if ($data) $this->updateFromData($data);
    }


    /**
     * @inheritdoc
     */
    public function restoreFromData() {
        $data = $this->datasource->getCurrentData();
        if ($data) {
            $this->internalUpdateFromData($data);
            $this->changes['attributes'] = [];
            $this->changes['relationships'] = [];
            $this->initialized = true;
        }
    }


    /**
     * @inheritdoc
     */
    public function isInitialized() {
        return $this->initialized;
    }


    /**
     * @inheritdoc
     */
    public static function fromResource(ResourceInterface $src, DatasourceInterface $datasource=null) {
        if (!$datasource) $datasource = $src->datasource;
        $targ = new static($datasource);

        $data = [
            'id' => $src->id,
            'type' => $src->resourceType,
            'attributes' => [],
            'relationships' => [],
        ];

        foreach(array_keys($targ->attributes) as $name) {
            if (array_key_exists($name, $src->attributes)) {
                $data['attributes'][$name] = $src->attributes[$name];
            }
        }
        foreach(array_keys($targ->relationships) as $name) {
            if (array_key_exists($name, $src->relationships)) {
                $data['relationships'][$name] = $src->relationships[$name];
            }
        }

        $targ->internalUpdateFromData($data);

        return $targ;
    }


    /**
     * internalUpdateFromData -- Updates data using the `updateFromData` method, but disables read-only checking
     * to allow default and sourced data to be loaded in.
     *
     * @see self::updateFromData
     */
    protected function internalUpdateFromData(array $data) {
        $this->honorReadOnly = false;
        $this->updateFromData($data);
        $this->honorReadOnly = true;
    }


    /**
     * @inheritdoc
     */
    public function updateFromData(array $data) {
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
                if (!method_exists($this, $setAttribute)) {
                    throw (new UnknownAttributeException("You've passed an attribute (`$n`) that is not valid for this resource."))
                        ->addOffender($n);
                }
                $this->$setAttribute($v);
            }
            unset($data['attributes']);
        }

        // Set Relationships
        if (array_key_exists('relationships', $data)) {
            foreach($data['relationships'] as $name => $rel) {

                // If it's not already a relationship instance, turn it into one
                if (!($rel instanceof RelationshipInterface)) {
                    // Set the relationship's name
                    $rel['name'] = $name;

                    // If we've got data, try to turn it into specific resources using the datasource
                    if (array_key_exists('data', $rel) && $rel['data'] !== null) {

                        // Check to see if it's a collection
                        $keys = array_keys($rel['data']);
                        $isCollection = true;
                        for($i = 0, $c = count($keys); $i < $c; $i++) {
                            if (!is_int($keys[$i])) {
                                $isCollection = false;
                                break;
                            }
                        }

                        if (!$isCollection) $rel['data'] = [$rel['data']];
                        foreach ($rel['data'] as $k => $r) {
                            $rel['data'][$k] = $this->datasource->inflateRelated($r);
                        }
                        if (!$isCollection) $rel['data'] = $rel['data'][0];
                    }

                    // Now create a valid relationship object
                    $rel = $this->getFactory()->newRelationship($rel);
                }

                // Validate that the relationship is settable
                $setRelationship = "set".ucfirst($name);
                if (!method_exists($this, $setRelationship)) {
                    throw (new UnknownRelationshipException("You've passed a relationship (`$name`) that is not valid for this resource."))
                        ->addOffender($name);
                }

                // Finally, set the relationship through it's setter
                $this->$setRelationship($rel->getData());
            }
            unset($data['relationships']);
        }

        // Now throw errors on leftover data
        if (count($data) > 0) {
            $e = new MalformedDataException("You have unrecognized data in your JsonApi Resource. Offending keys are: `".implode('`, `', array_keys($data))."`.");
            $e->addOffender("Resource (`$this->resourceType`)");
            $e->setOffendingData($data);
            throw $e;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function updateFromResource(ResourceInterface $r) {
        $this->updateFromData($r->jsonSerialize());
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setId($id) {
        //if ($this->validateReadOnly('id', $id === $this->getId())) {
            if ($this->id !== null && $id != $this->id) throw new DuplicateIdException("This resource already has an id. You cannot set a new ID for it.");
            $this->id = $id;
        //}
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResourceType() { return $this->resourceType; }

    /**
     * @inheritdoc
     */
    public function getId() { return $this->id; }


    /**
     * validateReadOnly -- Set an error if an attempt has been made to update a readonly field
     *
     * Since some values are difficult to check equality on (like DateTimes, for example), this function accepts a simple boolean `$changed`
     * flag to indicate whether or not there was an attempt to change the field. You'll usually
     *
     * @param string $field The name of the field for which to set an error
     * @param bool $changed Whether or not the field has changed
     * @return bool Whether or not the value should be changed
     */
    protected function validateReadOnly($field, $changed) {
        if (!is_bool($changed)) throw new \RuntimeException(
            "Programmer: You must pass a valid expression that evaluates to a boolean 'true' or 'false' for the ".
            "second argument of this method. For example, `\$this->validateReadOnly('myField', \$newVal != \$this->getMyField());`."
        );

        // If we're honoring readonly fields do the validation. (Otherwise, skip)
        if ($this->honorReadOnly) {
            if ($changed) {
                $this->setError($field, 'readonly', $this->getFactory()->newError([
                    "status" => 400,
                    "title" => "`$field` is read-only",
                    "detail" => "Field `$field` is a read-only field and can't be updated."
                ]));
            } else {
                $this->clearError($field, 'readonly');
            }
            return false;
        }
        return true;
    }


    /**
     * @inheritdoc
     */
    public function setBaseUri($uri) {
        $this->baseUri = rtrim($uri, '/');
    }


    /**
     * @inheritdoc
     */
    public function getCollectionLinkPath() {
        return "/{$this->resourceType}";
    }

    /**
     * @inheritdoc
     */
    public function getSelfLinkPath() {
        $path = $this->getCollectionLinkPath();
        if ($this->getId()) $path .= "/{$this->getId()}";
        return $path;
    }

    /**
     * @inheritdoc
     */
    public function getChanges($field = null) {
        // If requesting a specific field, send it back
        if ($field) {
            if (!$this->hasChanges($field)) {
                throw new FieldNotChangedException("Field `$field` has not changed.");
            }
            if (array_key_exists($field, $this->changes['attributes'])) {
                return $this->serializeAttribute($field);
            } else {
                return $this->changes['relationships'][$field]->getData();
            }
        }

        // Othewise, get all changes and send them back
        $changes = [
            'type' => $this->getResourceType(),
            'attributes' => [],
            'relationships' => $this->changes['relationships'],
        ];
        if ($this->getId()) $changes['id'] = $this->getId();

        foreach (array_keys($this->changes['attributes']) as $attr) {
            $changes['attributes'][$attr] = $this->serializeAttribute($attr);
        }

        return $changes;
    }

    /**
     * @inheritdoc
     */
    public function hasChanges($field=null) {
        if ($field) {
            return
                array_key_exists($field, $this->changes['attributes']) ||
                array_key_exists($field, $this->changes['relationships'])
            ;
        }

        return (
            count($this->changes['attributes']) +
            count($this->changes['relationships'])
        ) > 0;
    }

    /**
     * @inheritdoc
     */
    public function save() {
        $this->datasource->save($this);
        $this->setInitialState();
        return $this;
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
     * setInitialState -- Set the initial state of the resource
     */
    protected function setInitialState() {
        $this->initialState['attributes'] = $this->attributes;
        $this->initialState['relationships'] = [];
        foreach($this->relationships as $name => $rel) {
            $data = $rel->getData();
            if ($data) {
                if ($data instanceof ResourceCollectionInterface) {
                    $val = $data->summarize();
                } else {
                    $val = $data->getId() ?: "initial-".rand(1,10000);
                }
            } else {
                $val = null;
            }
            $this->initialState['relationships'][$name] = $val;
        }
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
     * _getAttributeValue -- Get's an attribute, checking to make sure the object has been initialized first
     *
     * @param string $name The name of the attribute to get
     * @return mixed The value of the attribute
     */
    protected function _getAttributeValue($name)
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new UnknownAttributeException("Programmer: Don't know how to retreive attribute `$name`.");
        }

        if (!$this->initialized) {
            $this->_initialize();
        }

        return $this->attributes[$name];
    }


    /**
     * _getRelationshipValue -- Get's a relationship value, checking to make sure that the object has been initialized first
     *
     * @param string $name The name of the relationship to get
     * @return ResourceInterface|ResourceCollectionInterface|null The value of the relationship
     */
    protected function _getRelationshipValue($name)
    {
        if (!array_key_exists($name, $this->relationships)) {
            throw new UnknownRelationshipException("Programmer: Don't know how to retreive relationship `$name`.");
        }

        if (!$this->initialized) {
            $this->_initialize();
        }

        return $this->attributes[$name];
    }


    /**
     * _initialize -- Initializes the object from the datasource, throwing an exception if there are already changed fields on it
     *
     * @return void
     */
    protected function _initialize() {
        if ($this->hasChanges()) {
            throw new \RuntimeException(
                "Programmer: This object has changes that would be overwritten by initializing it from the database. ".
                "You should make sure to initialize the resource before making any changes to it. Changes are: ".
                json_encode($this->getChanges())
            );
        }
        $this->datasource->initializeResource($this);
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
        if ($val == $this->initialState['attributes'][$name]) {
            if (array_key_exists($name, $this->changes['attributes'])) {
                unset($this->changes['attributes'][$name]);
            }
            return $this;
        }
        $this->attributes[$name] = $val;
        if ($this->trackChanges) $this->changes['attributes'][$name] = $val;
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
        $initial = $this->initialState['relationships'][$name];

        // If we've passed a null value, ...
        if (!$val) {

            // Then it hasn't changed if the initial value was also null
            if ($initial === null) $changed = false;

        // Else if the value is a resource, ...
        } elseif ($val instanceof ResourceInterface) {

            // Then it hasn't changed if the initial value's id is the same as the new one
            if ($initial && $val->getId() === $initial) $changed = false;

        // Else if the value is a collection, ...
        } elseif ($val instanceof ResourceCollectionInterface) {

            // Then it hasn't changed if the summary of the collection is the same as the initial summary
            if ($initial === $val->summarize()) $changed = false;

        // Otherwise, the value somehow got set to something unrecognizable
        } else {
            throw new \RuntimeException("Unrecognized relationship type! Relationships should be Resources, ResourceCollections or null.");
        }

        // Set the relationship regardless (this is our philosophy to avoid user surprises)
        $this->relationships[$name]->setData($val);

        // If changed, set the change (if applicable)
        if ($changed) {
            if ($this->trackChanges) {
                $this->changes['relationships'][$name] = $this->relationships[$name];
            }

        // Otherwise, unset the change (if applicable)
        } else {
            if (array_key_exists($name, $this->changes['relationships'])) {
                unset($this->changes['relationships'][$name]);
            }
        }


        return $this;
    }

    /**
     * get this resource's factory (optionally overridable by child classes)
     */
    protected function getFactory() {
        if (!$this->factory) $this->factory = new Factory();
        return $this->factory;
    }
}

