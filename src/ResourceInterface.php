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
     * getResourceType
     *
     * @return string $resourceType
     */
    public function getResourceType();

    /**
     * getId
     *
     * @return string $id
     */
    public function getId();

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
    public function setId($id);

    /**
     * Update fields from passed-in user data in jsonapi format
     *
     * @param array $data A JSON-API-formatted array of data defining attributes and relationships to set
     * @return void
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
     * getChanges
     *
     * @return array
     */
    public function getChanges();

    /**
     * hasChanges
     *
     * @return bool
     */
    public function hasChanges();

    /**
     * save to datasource
     *
     * @return static
     */
    public function save();

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

