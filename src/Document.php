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

    public function __construct(FactoryInterface $f, array $data=null) {
        $this->f = $f;
        if ($data) {
            if (array_key_exists('errors', $data)) {
                foreach($data['errors'] as $error) {
                    if (!($error instanceof Error)) $error = $this->f->newJsonApiError($error);
                    $this->addError($error);
                }
            }

            if (array_key_exists('data', $data)) {
                if ($data['data'] === null) $this->data = null;
                elseif ($data['data'] instanceof Resource || $data['data'] instanceof ResourceCollection) $this->data = $data['data'];
                elseif (array_key_exists('type', $data['data'])) $this->data = $this->f->newJsonApiResource($data['data'], true, $data['data']['type']);
                elseif (is_array($data['data'])) {
                    $rc = $this->f->newJsonApiResourceCollection();
                    foreach ($data['data'] as $r) $rc[] = $this->f->newJsonApiResource($r, true, $r['type']);
                    $this->data = $rc;
                } else {
                    throw new \InvalidArgumentException("Malformed `data` object in initial data array.");
                }
            }

            if (array_key_exists('links', $data)) {
                if (is_array($data['links'])) {
                    $links = $this->f->newJsonApiLinksCollection();
                    foreach($data['links'] as $name => $link) {
                        if (!($link instanceof LinkInterface)) {
                            if (is_array($link)) {
                                if (!array_key_exists('name', $link)) $link['name'] = $name;
                            } else {
                                $link = [
                                    'href' => $link,
                                    'name' => $name,
                                ];
                            }
                            $links[$name] = $this->f->newJsonApiLink($link);
                        } else {
                            $links[$link->getMemberName()] = $link;
                        }
                    }
                    $this->links = $links;
                } elseif ($data['links'] instanceof LinksCollectionInterface) {
                    $this->links = $data['links'];
                } else {
                    throw new \InvalidArgumentException("Links passed must be either an indexed collection of link objects or an array of link objects or data");
                }
            }

            if (array_key_exists('meta', $data)) {
                if ($data['meta'] instanceof Meta) $this->meta = $data['meta'];
                else $this->meta = $this->f->newJsonApiMeta($data['meta']);
            }

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
    public function getErrors() { return $this->errors ?: $this->f->newJsonApiErrorsCollection(); }
    public function getLinks() { return $this->links ?: $this->f->newJsonApiLinksCollection(); }
    public function getLink(string $name) {
        if ($this->links) return $this->links[$name];
        else return null;
    }
    public function getMeta() { return $this->meta; }
    public function getJsonapi() { return $this->jsonapi; }




    public function setData($data) {
        if (!($data instanceof ResourceInterface) && !($data instanceof ResourceCollectionInterface)) throw new \InvalidArgumentException("Data must be either a Resource or a ResourceCollection");
        $this->data = $data;
        return $this;
    }

    public function addError(ErrorInterface $e) {
        if (!$this->errors) $this->errors = $this->f->newJsonApiErrorsCollection();
        $this->errors[] = $e;
        return $this;
    }

    public function addLink(LinkInterface $l) {
        if (!$this->links) $this->links = $this->f->newJsonApiLinksCollection();
        $this->links[$l->getMemberName()] = $l;
        return $this;
    }

    public function setMeta(MetaInterface $m) {
        $this->meta = $m;
        return $this;
    }

    public function setJsonapi(array $jsonapi) {
        $this->jsonapi = $jsonapi;
        return $this;
    }




    public function jsonSerialize() {
        $data = [];
        if ($this->errors) $data['errors'] = $this->errors;
        else $data['data'] = $this->data;

        if ($this->links) $data['links'] = $this->links;
        if ($this->meta) $data['meta'] = $this->meta;
        if ($this->jsonapi) $data['jsonapi'] = $this->jsonapi;

        return $data;
    }
}

