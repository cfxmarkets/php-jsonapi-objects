<?php
namespace CFX\JsonApi;

class ResourceIdentifier implements ResourceIdentifierInterface {
    protected $id;
    protected $resourceType;
    protected $meta;

    /**
     * Construct a new ResourceIdentifier object
     *
     * @param array|null $data The initial data
     * @return void
     *
     * @throws MalformedDataException on unrecognized keys in the data array
     */
    public function __construct(array $data = null) {
        if ($data) {
            if (array_key_exists('id', $data)) {
                $this->id = $data['id'];
                unset($data['id']);
            }

            if (array_key_exists('type', $data)) {
                $this->resourceType = $data['type'];
                unset($data['type']);
            }

            if (array_key_exists('meta', $data)) {
                $this->meta = $data['meta'];
                unset($data['meta']);
            }


            // Now throw errors on leftover data
            if (count($data) > 0) {
                $e = new MalformedDataException("You have unrecognized data in your JsonApi Resource. Offending keys are: `".implode('`, `', array_keys($data))."`.");
                $e->addOffender("Resource (`$this->resourceType`)");
                $e->setOffendingData($data);
                throw $e;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getId() {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceType() {
        return $this->resourceType;
    }

    /**
     * {@inheritdoc}
     */
    public function setResourceType($type) {
        $this->resourceType = $type;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta() {
        return $this->meta;
    }

    /**
     * {@inheritdoc}
     */
    public function setMeta($meta) {
        if (!is_array($meta) && !($meta instanceof MetaInterface)) throw new \InvalidArgumentException("Meta must either be an array or a JsonApi Meta object");
        if (is_array($meta)) $meta = $this->getFactory()->newMeta($meta);
        $this->meta = $meta;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromResource(ResourceInterface $r) {
        $data = [];
        if ($r->getId()) $data['id'] = $r->getId();
        if ($r->getResourceType()) $data['id'] = $r->getResourceType();
        return new static($data);
    }


    /**
     * getFactory -- Gets a factory with which to instantiate other objects in the family
     */
    protected function getFactory() {
        return new Factory();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize() {
        if ($this->getId() === null || $this->getResourceType() === null) {
            $e = new UnserializableObjectStateException("Can't serialize resource identifier because of missing data");
            if (!$this->getId()) $e->addOffender('id');
            if (!$this->getResourceType()) $e->addOffender('type');
            throw $e;
        }

        $data = [
            'id' => $this->getId(),
            'type' => $this->getResourceType(),
        ];
        if ($this->getMeta() !== null) $data['meta'] = $this->getMeta();
        return $data;
    }
}

