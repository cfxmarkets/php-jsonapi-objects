<?php
namespace KS\JsonApi;

class Document implements DocumentInterface {
    protected $f;
    protected $data;
    protected $errors;
    protected $links;
    protected $meta;
    protected $jsonapi;
    protected $included;

    public function __construct(FactoryInterface $f, $data=null) {
        $this->f = $f;
        if ($data) {
            if (!array_key_exists('data', $data) && !array_key_exists('errors', $data)) throw new \InvalidArgumentException("You must provide either a `data` key containing a Resource or ResourceCollection, or an ErrorsCollection via the `errors` key to create a valid JsonApi Document.");

            if (array_key_exists('errors', $data)) {
                foreach($data['errors'] as $error) $this->addError($this->f->newJsonApiError($error));
            }

            if (array_key_exists('data', $data)) {
                if ($this->errors) throw new \InvalidArgumentException("You must have EITHER errors OR data to construct a valid JsonApi Document -- not both.");

                if ($data['data'] === null) $this->data = null;
                elseif (array_key_exists('type', $data['data'])) $this->data = $this->f->newJsonApiResource($data['data'], true, $data['data']['type']);
                else {
                    $rc = $this->f->newJsonApiResourceCollection();
                    foreach ($data['data'] as $r) $rc[] = $this->f->newJsonApiResource($r, true, $r['type']);
                    $this->data = $rc;
                }
            }

            if (array_key_exists('links', $data)) {
                //TODO: Validate links object
                $this->links = $data['links'];
            }

            if (array_key_exists('meta', $data)) $this->meta = $data['meta'];

            if (array_key_exists('jsonapi', $data)) {
                // TODO: Validate jsonapi object
                $this->jsonapi = $data['jsonapi'];
            }

            if (array_key_exists('included', $data)) {
                if (!is_array($data['included'])) throw new \InvalidArgumentException("If you pass an array of included resources, it must be an array, not an object or string or null or anything else.");
                $this->included = $this->f->newJsonApiResourceCollection();
                foreach($data['included'] as $r) $this->included[] = $this->f->newJsonApiResource($r, true, $r['type']);
            }
        }
    }


    public function getData() { return $this->data; }
    public function getErrors() { return $this->errors ?: []; }
    public function getLinks() { return $this->links; }
    public function getMeta() { return $this->meta; }
    public function getJsonApi() { return $this->jsonapi; }


    public function setData($data) {
        if (!($data instanceof Resource) && !($data instanceof ResourceCollection)) throw new \InvalidArgumentException("Data must be either a Resource or a ResourceCollection");
        $this->data = $data;
    }

    public function addError(Error $e) {
        if (!$this->errors) $this->errors = $this->f->newJsonApiErrorsCollection();
        $this->errors[] = $e;
    }

    public function jsonSerialize() {
        $data = [];
        if ($this->errors) $data['errors'] = $this->errors;
        else {
            if ($this->data) $data['data'] = $this->data;
            else $data['data'] = null;
        }

        if ($this->links) $data['links'] = $this->links;
        if ($this->meta) $data['meta'] = $this->meta;
        if ($this->jsonapi) $data['jsonapi'] = $this->jsonapi;

        return $data;
    }
}

