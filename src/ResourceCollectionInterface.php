<?php
namespace CFX\JsonApi;

interface ResourceCollectionInterface extends DataInterface, CollectionInterface {
    /**
     * convertTo -- Convert all members to the type specified by $type
     *
     * @param string $type The type of resource to convert to
     * @return static
     */
    public function convertTo($type);
}

