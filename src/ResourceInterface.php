<?php
namespace CFX\JsonApi;

interface ResourceInterface extends DataInterface, \JsonSerializable, \KS\ErrorHandlerInterface {
    /**
     * fromResource -- Creates another, different resource object from the given resource
     *
     * This method is designed to allow "filtering" of properties and resources between contexts, for
     * example when converting from a "public-space" resource like an SDK resource to a "private-space"
     * resource for further manipulation.
     *
     * Note: This method is resource type-sensitive. It will choke when trying to create a resource
     * from a resource of a different declared type. It was not designed to enable polymorphic cloning,
     * rather, it was to facilitate a security boundary between public-space and protected-space resources.
     *
     * Other than that, it is a naive method: If there are properties that match the properties
     * of the resource being created, they will be used to populate its data. Because of this, it does NOT
     * populate information about initialization status or changes.
     *
     * @param ResourceInterface
     * @return static
     */
    public static function fromResource(ResourceInterface $r);

    /**
     * Gets the JSON API type of this resource
     *
     * @return string $resourceType
     */
    public function getResourceType();

    /**
     * Gets the resource's id
     *
     * @return null|string $id
     */
    public function getId();

    /**
     * Sets the resource's id, if not already set
     *
     * If the resource ID is already set, should throw a DuplicateIdException
     *
     * @param string $id The id of the object
     * @return static
     *
     * @throws DuplicateIdException
     */
    public function setId($id);

    /**
     * Update fields from passed-in user data in jsonapi format
     *
     * @param array $data A JSON-API-formatted array of data defining attributes and relationships to set
     * @return static
     */
    public function updateFromData(array $data);


    /**
     * updateFromResource -- Update fields from another resource object
     *
     * @param ResourceInterface $resource
     * @return static
     */
    public function updateFromResource(ResourceInterface $r);

    
    /**
     * Restore an object from data persisted to a secure datasource
     *
     * This method is called from the constructor ONLY and is intended to allow the datasource to reliably
     * inflate objects. It may also be called by a datasource to update an object with the returned fields
     * in the event of a `save`.
     */
    public function restoreFromData();

    /**
     * Checks to see whether or not a resources has been initialized
     *
     * @return bool
     */
    public function isInitialized();

    /**
     * Initializes the object from the datasource
     *
     * Should throw an exception if there are already changed fields on the object
     *
     * @return static
     */
    public function initialize();

    /**
     * Refreshes a resource from datasource
     *
     * This may be useful if, for example, an object's fields have been updated elsewhere and those
     * updates need to be propagated to already-existing program objects
     *
     * @return static
     */
    public function refresh();

    /**
     * hasChanges
     *
     * Checks to see whether the object -- or optionally a specific field -- has changed
     *
     * @param string|null $field An optional field to check, specifically
     * @return bool
     */
    public function hasChanges($field=null);

    /**
     * getChanges
     *
     * Gets changes for the object, or optionally the new value for a specific field
     *
     * @param string|null $field An optional field to return
     * @return array
     *
     * @throws FieldNotChangedException
     */
    public function getChanges($field=null);

    /**
     * Save to the attached datasource
     *
     * Should throw an exception if there is no attached datasource
     *
     * @return static
     */
    public function save();

    /**
     * Convert to different type (for example, "private" or "public")
     *
     * @param string $type The type of resource to convert to (usually 'public' or 'private')
     * @return mixed The new resource
     */
    public function convertTo($type);

    /**
     * setBaseUri -- Set the uri off of which links are composed
     *
     * @param string $uri A valid string uri (without trailing slash)
     * @return static
     */
    public function setBaseUri($uri);

    /**
     * getCollectionLinkPath
     *
     * @return string The path for the collection
     */
    public function getCollectionLinkPath();

    /**
     * getSelfLinkPath
     *
     * @return string
     */
    public function getSelfLinkPath();
}

