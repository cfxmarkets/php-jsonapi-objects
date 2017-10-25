<?php
namespace CFX\JsonApi;

class Relationship implements RelationshipInterface {
    protected $factory;

    protected $name;
    protected $links;
    protected $meta;
    protected $data;

    public function __construct(array $data) {
        // Set name (mandatory)
        if (!array_key_exists('name', $data)) throw new \RuntimeException("Programmer: You must set the `name` key in the `\$data` array when instantiating a Relationship. (This is not part of the JSON API spec, so you usually have to do this manually.)");
        $this->name = $data['name'];
        unset($data['name']);

        if (!array_key_exists('data', $data)) $data['data'] = null;

        // Set data
        if ($data['data'] === null) $this->data = null;
        elseif ($data['data'] instanceof DataInterface) $this->data = $data['data'];
        elseif (array_key_exists('id', $data['data'])) $this->data = $this->getFactory()->newResource($data['data'], array_key_exists('type', $data['data']) ? $data['data']['type'] : null);
        else {
            $rc = $this->data = $this->getFactory()->newResourceCollection();
            foreach($data['data'] as $r) {
                if ($r instanceof DataInterface) {
                    $rc[] = $r;
                } else {
                    $rc[] = $this->getFactory()->newResource($r, array_key_exists('type', $r) ? $r['type'] : null);
                }
            }
        }
        unset($data['data']);

        // Set links, if applicable
        if (array_key_exists('links', $data)) {
            $this->links = $data['links'];
            unset($data['links']);
        }

        // Set meta, if applicable
        if (array_key_exists('meta', $data)) {
            $this->meta = $data['meta'];
            unset($data['meta']);
        }

        // If there's extra data, throw an exception
        if (count($data) > 0) {
            $e = new MalformedDataException("You have unrecognized data in your JsonApi Relationship. Offending keys are: `".implode('`, `', array_keys($data))."`.");
            $e->addOffender("Relationship (`$this->name`)");
            $e->setOffendingData($data);
            throw $e;
        }
    }

    public function getName() { return $this->name; }
    public function getLinks() { return $this->links; }
    public function getMeta() { return $this->meta; }
    public function getData() { return $this->data; }

    public function setData($d=null) {
        // Typecheck
        if ($d !== null) {
            if (!($d instanceof ResourceInterface) && !($d instanceof ResourceCollectionInterface)) {
                $type = gettype($d);
                if ($type == 'object') $type = get_class($d);
                throw new \InvalidArgumentException("Value passed to `setData` must be either a Resource (`ResourceInterface`), a Resource Collection (`ResourceCollectionInterface`), or null. (`$type` given)");
            }
        }

        $this->data = $d;
        return $this;
    }

    public function getMemberName() { return $this->getName(); }

    public function jsonSerialize() {
        $data = [
            'data' => $this->data ? $this->data->jsonSerialize(false) : null
        ];
        if ($this->links) $data['links'] = $this->links;
        if ($this->meta) $data['meta'] = $this->meta;
        return $data;
    }

    /**
     * get this resource's factory (optionally overridable by child classes)
     */
    protected function getFactory() {
        if (!$this->factory) $this->factory = new Factory();
        return $this->factory;
    }
}

