<?php
namespace CFX\JsonApi;

abstract class AbstractResource implements ResourceInterface {
    use \KS\ErrorHandlerTrait {
        \KS\ErrorHandlerTrait::setError as setJsonApiError;
    }

    /**
     * @var \CFX\JsonApi\DatasourceInterface This resource's instance of the Datasource.
     */
    protected $datasource;

    /**
     * @var \CFX\JsonApi\FactoryInterface This resource's factory instance for instantiating other jsonapi family members
     *
     * NOTE: This can be changed by overriding the `getFactory` method
     */
    protected $factory;

    /**
     * @var string JSON API id field
     */
    protected $id;

    /**
     * @var string JSON API resource type field
     */
    protected $resourceType;

    /**
     * @var array JSON API attributes container
     */
    protected $attributes = [];

    /**
     * @var array JSON API relationships container
     */
    protected $relationships = [];

    /**
     * @var bool Flag to trigger honoring of read-only attributes and relationships
     *
     * This is an internal flag that allows us to set readonly fields from the datasource or from
     * more privileged derivative classes, but sets errors on the object when users attempt to
     * set read-only fields.
     */
    private $honorReadOnly = true;

    /**
     * @var array Container for storing initial object state, for comparison in change tracking
     */
    protected $initialState = [ 'attributes' => [], 'relationships' => [] ];

    /**
     * @var array Container for storing changes
     */
    protected $changes = [ 'attributes' => [], 'relationships' => [] ];

    /**
     * @var bool Flag indicating whether or not we should track changes.
     */
    protected $trackChanges = true;

    /**
     * @var bool Flag to indicate that the object is in the process of being initialized. (Affects logic that retrieves
     * missing data when setting uninitialized fields.)
     */
    protected $initializing = false;

    /**
     * @var bool Flag to indicate whether or not this object was initialized from a valid datasource
     */
    protected $initialized = false;

    /**
     * @var array Relationship-indexed array indicating whether or not each to-many relationship has been initialized yet
     */
    protected $initializedRelationships = [];

    /**
     * @var \CFX\JsonApi\MetaInterface|null
     */
    protected $meta = null;


    /**
     * Constructor: constructs a Resource object
     *
     * If $data is provided, it is used as USER DATA (i.e., untrusted) to set fields. If $data contains a `type` field, it
     * cannot conflict with any pre-set type or an Exception will be thrown.
     *
     * You may define the valid attributes and relationships in the `$attributes` and `relationships` arrays. Either may have
     * default values (if it makes sense). These are the arrays that are dumped when the object is
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
     *     protected $relationships = [ 'addresses' => null, 'boss' => null ];
     *
     *     public function setName($name);
     *     public function setDob($dob);
     *     public function setActive($active);
     *     public function setAddresses(ResourceCollectionInterface $addresses = null);
     *     public function addAddress(AddressInterface $address);
     *     public function hasAddress(AddressInterface $address);
     *     public function removeAddress(AddressInterface $address);
     *     public function setBoss(PersonInterface $boss = null);
     *
     * @param DatasourceInterface $datasource A datasource instance
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
            throw new \RuntimeException("Programmer: Looks like you may have forgotten to add a getter or setter for attribute `{$e->getOffenders()[0]}` in `".get_class($this).". All attributes should have getters and setters, though these don't have to be in the public scope.");
        } catch (UnknownRelationshipException $e) {
            throw new \RuntimeException("Programmer: Looks like you may have forgotten to add a getter or setter for relationship `{$e->getOffenders()[0]}` in `".get_class($this).". All relationships should have getters and setters, though these don't have to be in the public scope. (Relationships setters should set the *data* for the relationship, that is, they should receive a ResourceInterface, a ResourceCollectionInterface, or null.)");
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

        $initialState = [
            'attributes' => [],
            'relationships' => [],
        ];

        $changes = [
            'attributes' => [],
            'relationships' => [],
        ];

        foreach(array_keys($targ->attributes) as $name) {
            if (array_key_exists($name, $src->attributes)) {
                $data['attributes'][$name] = $src->attributes[$name];
                $initialState['attributes'][$name] = $src->initialState['attributes'][$name];
                if (array_key_exists($name, $src->changes['attributes'])) {
                    $changes['attributes'][$name] = $src->changes['attributes'][$name];
                }
            }
        }
        foreach(array_keys($targ->relationships) as $name) {
            if (array_key_exists($name, $src->relationships)) {
                $data['relationships'][$name] = $src->relationships[$name];
                $initialState['relationships'][$name] = $src->initialState['relationships'][$name];
                if (array_key_exists($name, $src->changes['relationships'])) {
                    $changes['relationships'][$name] = $src->changes['relationships'][$name];
                }
            }

            if (array_key_exists($name, $src->initializedRelationships)) {
                $targ->initializedRelationships[$name] = $name;
            }
        }

        $targ->internalUpdateFromData($data);
        $targ->initialState = array_replace_recursive($targ->initialState, $initialState);
        $targ->changes = array_replace_recursive($targ->changes, $changes);
        $targ->initialized = $src->initialized;

        $targ->meta = $src->meta;

        return $targ;
    }


    /**
     * internalUpdateFromData -- Updates data using the `updateFromData` method, but disables read-only checking
     * to allow default and sourced data to be loaded in.
     *
     * @see self::updateFromData
     */
    protected function internalUpdateFromData(array $data) {
        $initializing = $this->initializing;
        $this->initializing = true;
        $this->readOnlyOverride(function() use ($data) {
            return $this->updateFromData($data);
        });
        $this->initializing = $initializing;
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

        // Set meta
        if (array_key_exists('meta', $data)) {
            if ($data["meta"] && !($data["meta"] instanceof MetaInterface)) {
                $data["meta"] = new Meta($data["meta"]);
            }
            $this->setMeta($data["meta"] === null ? null : $data["meta"]);
            unset($data['meta']);
        }

        // Now throw errors on leftover data
        if (count($data) > 0) {
            throw (new MalformedDataException("You have unrecognized data in your JsonApi Resource. Offending keys are: `".implode('`, `', array_keys($data))."`."))
                ->addOffender("Resource (`$this->resourceType`)")
                ->setOffendingData($data);
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
        if ($this->id !== null && $id != $this->id && !$this->initializing) {
            throw new DuplicateIdException("This resource already has an id (`$this->id`). You cannot set a new ID for it (`$id`).");
        }
        $this->id = $id;
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
     * @param mixed $val The new value of the field
     * @return bool Whether or not the value should be changed
     */
    protected function validateReadOnly($field, $val) {
        // If we're honoring readonly fields do the validation. (Otherwise, skip)
        if ($this->honorReadOnly) {
            if ($this->valueDiffersFromInitial($field, $val)) {
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
     * readOnlyOverride -- Override read-only setting for the given method and value
     *
     * This method can be used by derivative classes to override the read-only setting for a given field. Normally,
     * a public-facing class might declare a setter with the `validateReadOnly` method in it to prevent user-originating
     * changes to the field. Internal extensions of that class can wrap a call to `parent::setField` in a closure and
     * send it to this function, which will temporarily disable read-only monitoring while executing the logic within
     * the Closure.
     *
     * @param \Closure $func The function to execute with read-only mode turned off
     * @return mixed Returns whatever the Closure returned (should be `$this`, but doesn't have to be)
     */
    protected function readOnlyOverride(\Closure $method)
    {
        $readonly = $this->honorReadOnly;
        $this->honorReadOnly = false;
        $result = $method();
        $this->honorReadOnly = $readonly;
        return $result;
    }


    /**
     * validateRequired -- Set an error if an empty value has been set for the given attribute or relationship
     *
     * @param string $field The name of the field
     * @param mixed $val The new value of the field
     * @return bool Whether or not to proceed ('true' means it's valid, 'false' means its not)
     */
    protected function validateRequired($field, $val) {
        if ($val === null || $val === '') {
            $this->setError($field, 'required', [
                "title" => "Missing Required Field `$field`",
                "detail" => "Field `$field` is a required field and cannot be null."
            ]);
            return false;
        } else {
            $this->clearError($field, 'required');
            return true;
        }
    }

    /**
     * valueDiffersFromInitial -- An internal method that checks to see whether a given value is diffent
     * from the intial value set.
     *
     * @param string $name The field name (attribute or relationship)
     * @param mixed $val The new value
     * @return bool
     */
    protected function valueDiffersFromInitial($name, $val) {
        if (array_key_exists($name, $this->initialState['attributes'])) {
            $test = $this->initialState['attributes'][$name];
        } elseif (array_key_exists($name, $this->initialState['relationships'])) {
            $test = $this->initialState['relationships'][$name];
        } else {
            throw new \RuntimeException(
                "Programmer: `$name` is not registered in either the `attributes` or `relationships` initial state. Is this ".
                "a typo?"
            );
        }

        // If it's Data, it must be a relationship, and relationship initial values are stored in string format
        if ($val instanceof DataInterface) {
            if ($val instanceof ResourceInterface) {
                return $val->getId() !== $test;
            } else if ($val instanceof ResourceCollectionInterface) {
                return $test !== $val->summarize();
            }
        }

        return $val !== $test;
    }


    /**
     * getInitial -- Gets the initial value for an attribute or relationship
     *
     * @param string $name The field name (attribute or relationship)
     * @return mixed The initial value of the field
     */
    protected function getInitial($name) {
        if (array_key_exists($name, $this->initialState['attributes'])) {
            return $this->initialState['attributes'][$name];
        } elseif (array_key_exists($name, $this->initialState['relationships'])) {
            return $this->initialState['relationships'][$name];
        } else {
            throw new \RuntimeException(
                "Programmer: `$name` is not registered in either the `attributes` or `relationships` initial state. Is this ".
                "a typo?"
            );
        }
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
        ];

        if (count($this->changes['relationships']) > 0) {
            $changes['relationships'] = $this->changes['relationships'];
        }

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
     * NOTE: This method was added in an emergency. There are a lot of problems regarding accessing resource fields
     * after deletion. These should be resolved before this method can be depended upon.
     */
    public function delete() {
        $this->datasource->delete($this);
        $this->id = null;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function convertTo($type) {
        return $this->datasource->convert($this, $type);
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

        if ($this->resourceType) {
            $data['type'] = $this->resourceType;
        }

        if ($this->id) {
            $data['id'] = $this->id;
        }

        if ($this->meta) {
            $data["meta"] = $this->meta;
        }

        if (!$fullResource) return $data;

        if (count($this->attributes) > 0) {
            foreach($this->attributes as $name => $v) $data['attributes'][$name] = $this->serializeAttribute($name);
        }
        if (count($this->relationships) > 0) {
            $data['relationships'] = [];
            foreach($this->relationships as $r) {
                $data['relationships'][$r->getName()] = $r;
            }
        }
        return $data;
    }

    /**
     * setInitialState -- Set the initial state of the resource
     *
     * @return void
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
            throw (new UnknownAttributeException("Programmer: Don't know how to retreive attribute `$name`."))
                ->addOffender($name);
        }

        if (!$this->getId()) {
            return $this->attributes[$name];
        }

        $this->initialize();

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
            throw (new UnknownRelationshipException("Programmer: Don't know how to retreive relationship `$name`."))
                ->addOffender($name);
        }

        $this->initialize();

        return $this->relationships[$name]->getData();
    }


    /**
     * @inheritdoc
     */
    public function initialize() {
        if (!$this->initialized && !$this->initializing && $this->getId()) {
            /*
             * TODO: Fix this. Currently, non-null default data is registered as "changes" on object instantiation. This is
             * desirable, but it has the side effect of causing this initialization to break even when the changes are "throw
             * away". Perhaps a good opportunity to implement ResourcePointers.
             *
            if ($this->hasChanges()) {
                throw new \RuntimeException(
                    "Programmer: This object has changes that would be overwritten by initializing it from the database. ".
                    "You should make sure to initialize the resource before making any changes to it. Changes are: ".
                    json_encode($this->getChanges())
                );
            }
             */
            try {
                $this->datasource->initializeResource($this);
                $this->setInitialState();
            } catch (\CFX\Persistence\ResourceNotFoundException $e) {
                throw new \CFX\CorruptDataException(
                    "Programmer: Your system has corrupt data. You've attempted to initialize a resource of ".
                    "type `{$this->getResourceType()}` with id `{$this->getId()}`, but that resources doesn't exist ".
                    "in the specified database."
                );
            }
        }
        return $this;
    }


    /**
     * @inheritdoc
     */
    public function refresh()
    {
        $this->initializedRelationships = [];
        $this->initialized = false;
        return $this->initialize();
    }


    /**
     * @inheritdoc
     */
    public function getMeta(): ?MetaInterface
    {
        return $this->meta;
    }


    /**
     * @inheritdoc
     */
    public function setMeta(?MetaInterface $meta = null): ResourceInterface
    {
        $this->meta = $meta;
        return $this;
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
        $this->initialize();

        if (!array_key_exists($name, $this->attributes)) {
            throw (new UnknownAttributeException("You're trying to set an attribute (`$name`) that is not valid for this resource."))
                ->addOffender($name);
        }

        $this->attributes[$name] = $val;

        if (!$this->valueDiffersFromInitial($name, $val)) {
            if (array_key_exists($name, $this->changes['attributes'])) {
                unset($this->changes['attributes'][$name]);
            }
        } else {
            if ($this->trackChanges) $this->changes['attributes'][$name] = $val;
        }

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
        $this->initialize();

        if (!array_key_exists($name, $this->relationships)) {
            throw (new UnknownRelationshipException("You're trying to set a relationship (`$name`) that is not valid for this resource."))
                ->addOffender($name);
        }

        // Set the relationship regardless (this is our philosophy to avoid user surprises)
        $this->relationships[$name]->setData($val);

        // If changed, set the change (if applicable)
        if ($this->valueDiffersFromInitial($name, $val)) {
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
     * Get this resource's factory (optionally overridable by child classes)
     *
     * @return \CFX\JsonApi\FactoryInterface
     */
    protected function getFactory() {
        if (!$this->factory) $this->factory = new Factory();
        return $this->factory;
    }

    /**
     * Make it easier to set errors on objects
     *
     * @param string $field The field name to which to attach the error
     * @param string $errorType An arbitrary type specifier for adding multiple different types of errors
     * to a field
     * @param array $error An array of JSON API-compatible error fields (like "title" and "detail")
     * @return static
     */
    protected function setError($field, $errorType, $error) {
        if (is_array($error)) {
            if (!array_key_exists('status', $error)) {
                $error['status'] = 400;
            }
            $error = $this->getFactory()->newError($error);
        }

        return $this->setJsonApiError($field, $errorType, $error);
    }
}

