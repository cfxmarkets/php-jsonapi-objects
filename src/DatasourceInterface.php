<?php
namespace CFX\JsonApi;

interface DatasourceInterface {
    /**
     * getCurrentData -- Get any data that was just retrieved from the datasource
     *
     * This method is intended to provide a private "handshake" between a datasource and the resource that it's
     * creating. While it must be public, it should only return data in very specific instances, i.e., when the datasource
     * has been "loaded" with data and then itself instantiates an object that consumes that data. In this event, the
     * datasource should null out its data as soon as the target resource has been inflated, thus providing a relatively
     * secure way for a resource to know when its data is coming from an authoritative source.
     *
     * @return array $data
     */
    public function getCurrentData();

    /**
     * Create a new instance of the Resource class represented by this datasource
     *
     * @param array|null $data User-provided (i.e., unsafe) data with which to initialize the new resource
     * @param string|null $type An internal type specifying which permutation of this class you'd like (usually used
     * to select public-facing vs private or internal classes)
     * @return \CFX\JsonApi\ResourceInterface
     */
    public function create(array $data = null, $type = null);

    /**
     * newCollection -- Get a new collection of this type of resource
     * 
     * @param array|null $collection An array of objects with which to initialize the collection
     * @return ResourceCollectionInterface
     */
    public function newCollection(array $collection=[]);

    /**
     * convert -- Converts between two types of instances
     *
     * Used primarily for converting between public and private resource types. You would use this, for example, to
     * parse an incoming request using a public datatype (say, `User`), then convert it to a private datatype with
     * more capabilities (say, `InternalUser`). (The `InternalUser` class might allow you to do things like set status
     * and role that the public `User` class doesn't.)
     *
     * Should throw an UnknownResourceTypeException when the `$convertTo` parameter is not recognized
     *
     * @param \CFX\JsonApi\ResourceInterface $src The source object to convert
     * @param string $convertTo A string describing what to convert to
     * @throws UnknownResourceTypeException when it doesn't know how to convert to the destination type
     */
    public function convert(\CFX\JsonApi\ResourceInterface $src, $convertTo);

    /**
     * save -- Persist the given resource
     *
     * @param \CFX\JsonApi\ResourceInterface $r The resource to save
     * @return \CFX\JsonApi\ResourceInterface
     */
    public function save(\CFX\JsonApi\ResourceInterface $r);

    /**
     * get -- Get resources, optionally filtered by a query
     *
     * The optional query should be a string DSL query that you are prepared to parse. This can be as simple or
     * as complex as you'd like, but was left intentionally vague to facilitate arbitrarily complex queries across
     * any type of persistence system.
     *
     * @param string $query An optional query with which to filter resources.
     * @return \CFX\JsonApi\ResourceInterface|ResourceCollectionInterface The resource or resource collection returned
     * by the query. If the query includes an ID, then a single resource is returned (or exception thrown). If it doesn't include an
     * id, then a (possibly empty) collection is returned.
     *
     * @throws ResourceNotFoundException
     */
    public function get($q=null);

    /**
     * getRelated -- Get the resource or collection represented by the named relationship
     *
     * **NOTE:** At the time of this writing, this method has some ambiguous uses. When getting a to-one relationship,
     * the `$id` paramter is the ID of the related resource itself. When getting a to-many relationship, the `$id`
     * parameter represents the ID of the requesting resource. Hopefully this will be resolved soon.
     *
     * @param string $name The name of the relationship on the object
     * @param string $id The id of the object requesting the related resource
     * @return ResourceCollectionInterface|ResourceInterface $resource The related resource or collection
     */
    public function getRelated($name, $id);

    /**
     * delete -- Delete a resource
     *
     * If the resources requested for deletion does not exist, no exception is thrown, since the end goal of the operation is that the
     * resource no longer be in the database.
     *
     * @param \CFX\JsonApi\ResourceInterface|id The resource or resource id to delete
     * @return static
     */
    public function delete($r);

    /**
     * inflateRelated -- Turn data for a related resource into an object
     *
     * This method is made available to resources so they may attempt to create more specifically typed resources to represent their
     * relationships. It is an opportunity to delegate creation to other known datasources in the ecosystem.
     *
     * @param array $data The data representing the related resource
     * @return \CFX\JsonApi\ResourceInterface
     */
    public function inflateRelated(array $data);

    /**
     * initializeResource -- Get data for a resource from the database and update the resource with it
     *
     * @param ResourceInterface $r The resource to initialize
     * @return static
     */
    public function initializeResource(ResourceInterface $r);
}


