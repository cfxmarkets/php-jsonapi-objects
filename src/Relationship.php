<?php
namespace KS\JsonApi;

class Relationship implements RelationshipInterface {
    protected $f;

    protected $name;
    protected $links;
    protected $meta;
    protected $data;

    public function __construct(FactoryInterface $f, $data) {
        $this->f = $f;

        if (!array_key_exists('name', $data)) throw new \InvalidArgumentException("To construct a Relationship, you must pass a `name` key containing the name of the resource.");
        $this->name = $data['name'];

        if (!array_key_exists('data', $data)) $data['data'] = null;

        if ($data['data'] === null) $this->data = null;
        elseif (array_key_exists('id', $data['data'])) $this->data = $this->f->newJsonApiResource($data['data'], array_key_exists('type', $data['data']) ? $data['data']['type'] : null);
        else {
            $rc = $this->data = $this->f->newJsonApiResourceCollection();
            foreach($data['data'] as $r) $rc[] = $this->f->newJsonApiResource($r, array_key_exists('type', $r) ? $r['type'] : null);
        }

        if (array_key_exists('links', $data)) $this->links = $data['links'];
        if (array_key_exists('meta', $data)) $this->meta = $data['meta'];
    }

    public function getName() { return $this->name; }
    public function getLinks() { return $this->links; }
    public function getMeta() { return $this->meta; }
    public function getData() { return $this->data; }

    public function setData($d=null) {
        // Typecheck
        if ($d !== null) {
            if (!($d instanceof BaseResourceInterface) && !($d instanceof ResourceCollectionInterface)) {
                $type = gettype($d);
                if ($type == 'object') $type = get_class($d);
                throw \InvalidArgumentException("Value passed to `setData` must be either a Resource (`BaseResourceInterface`), a Resource Collection (`ResourceCollectionInterface`), or null. (`$type` given)");
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
}

