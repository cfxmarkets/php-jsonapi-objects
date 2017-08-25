<?php
namespace KS\JsonApi;

class Document implements DocumentInterface {
    protected $data;
    protected $errors;
    protected $links;
    protected $meta;
    protected $jsonapi;

    public function __construct(array $data=null) {
        if ($data) {
            if (!array_key_exists('data', $data) && !array_key_exists('errors', $data)) throw new \InvalidArgumentException("You must provide either a `data` key containing a Resource or ResourceCollection, or an ErrorsCollection via the `errors` key to create a valid JsonApi Document.");

            if (array_key_exists('errors', $data)) {
                foreach($data['errors'] as $error) $this->addError(new Error($error));
            }

            if (array_key_exists('data', $data)) {
                if ($this->errors) throw new \InvalidArgumentException("You must have EITHER errors OR data to construct a valid JsonApi Document -- not both.");

                if ($data['data'] === null) $this->data = null;
                elseif (array_key_exists('type', $data['data'])) $this->data = new Resource($data['data']);
                else {
                    $rc = new ResourceCollection();
                    foreach ($data['data'] as $r) $rc[] = new Resource($r);
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
        }
    }


    public function getData() { return $this->data; }
    public function getErrors() { return $this->errors; }
    public function getLinks() { return $this->links; }
    public function getMeta() { return $this->meta; }
    public function getJsonApi() { return $this->jsonapi; }


    public function setData($data) {
        if (!($data instanceof Resource) && !($data instanceof ResourceCollection)) throw new \InvalidArgumentException("Data must be either a Resource or a ResourceCollection");
        $this->data = $data;
    }

    public function addError(Error $e) {
        if (!$this->errors) $this->errors = new ErrorsCollection();
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

