<?php
namespace CFX\JsonApi;

interface ResourceIdentifierInterface extends DataInterface {
    /**
     * self-explanatory
     */
    public function getId();

    /**
     * self-explanatory
     */
    public function setId($id);

    /**
     * self-explanatory
     */
    public function getResourceType();

    /**
     * self-explanatory
     */
    public function setResourceType($type);

    /**
     * self-explanatory
     */
    public function getMeta();

    /**
     * self-explanatory
     */
    public function setMeta($meta);

    /**
     * self-explanatory
     */
    public static function fromResource(ResourceInterface $r);
}

