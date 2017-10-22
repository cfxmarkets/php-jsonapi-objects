<?php
namespace KS\JsonApi;

interface DatasourceInterface {
    function newJsonApiDocument($data=null);
    function newJsonApiResource($data=null, $type=null);
    function newJsonApiRelationship($data);
    function newJsonApiError($data);
    function newJsonApiMeta($data=null);
    function newJsonApiLink($data=null);
    function newJsonApiResourceCollection($resources=[]);
    function newJsonApiErrorsCollection($errors=[]);
    function newJsonApiLinksCollection($links=[]);
    function getCurrentData();
    function create($data=null);
    function get($q=null);

    /**
     * new -- Get a new instance of the Resource class represented by this client
     *
     * @return \CFX\BaseResourceInterface
     */
    public function create();

    /**
     * convert -- Converts between two types of instances (@see \KS\JsonApi\ContextInterface::convertJsonApiResource)
     *
     * Used primarily for converting between public and private resource types
     */
    public function convert(ResourceInterface $src, $convertTo);

    /**
     * save -- Persist the given resource
     *
     * @param \CFX\BaseResourceInterface $r The resource to save
     * @return \CFX\BaseResourceInterface
     */
    public function save(DataObjectInterface $r);

    /**
     * get -- Get resources, optionally filtered by a query
     *
     * @param string $query An optional query with which to filter resources.
     * @return \CFX\BaseResourceInterface|\CFX\ResourceCollectionInterface The resource or resource collection returned
     * by the query. If the query includes an ID, then a single resource is returned (or exception thrown). If it doesn't include an
     * id, then an empty collection may be returned if there are no results.
     *
     * @throws \CFX\ResourceNotFoundException
     */
    public function get($q=null);

    /**
     * delete -- Delete a resource
     *
     * If the resources requested for deletion does not exist, no exception is thrown, since the end goal of the operation is that the
     * resource no longer be in the database.
     *
     * @param \CFX\BaseResourceInterface|id The resource or resource id to delete
     * @return void
     */
    public function delete($r);

    /**
     * getCurrentData -- handshake method between new object and the datasource
     *
     * If this datasource inflates a new object, the new object should use this method in its constructor to get the data retrieved
     * from the datasource. Calling this method should wipe the `currentData` property, such that data is only available to objects
     * directly instantiated by this datasource.
     */
    public function getCurrentData();
}

