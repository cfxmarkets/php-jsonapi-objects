<?php
namespace KS\JsonApi;

class Document implements DocumentInterface {
    protected $version = "1.0";

    protected $baseUrl;
    protected $f;
    protected $data;
    protected $errors;
    protected $links;
    protected $meta;
    protected $included;

    public function __construct(FactoryInterface $f, $data=null) {
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
                elseif ($data['data'] instanceof BaseResourceInterface || $data['data'] instanceof ResourceCollectionInterface) $this->data = $data['data'];
                elseif (is_array($data['data'])) {
                    $isCollection = is_numeric(implode('', array_keys($data['data'])));
                    if ($isCollection) {
                        $rc = $this->f->newJsonApiResourceCollection();
                        foreach ($data['data'] as $r) $rc[] = $this->f->newJsonApiResource($r, $r['type']);
                        $this->data = $rc;
                    } else {
                        if (!array_key_exists('type', $data['data'])) throw new \InvalidArgumentException("If you provide a resource via the `data` key, you MUST specify its type via the `data::type` key (e.g., [ 'data' => [ 'type' => 'my-resources', 'attributes' => [ ... ] ] ]).");
                        $this->data = $this->f->newJsonApiResource($data['data'], $data['data']['type']);
                    }
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

            if (array_key_exists('included', $data)) {
                if (!is_array($data['included'])) throw new \InvalidArgumentException("If you pass an array of included resources, it must be an array, not an object or string or null or anything else.");
                $this->included = $this->f->newJsonApiResourceCollection();
                foreach($data['included'] as $r) $this->included[] = $this->f->newJsonApiResource($r, $r['type']);
            }
        }
    }


    public function getBaseUrl() { return $this->baseUrl; }
    public function getData() { return $this->data; }
    public function getErrors() { return $this->errors ?: $this->f->newJsonApiErrorsCollection(); }
    public function getLinks() { return $this->links ?: $this->f->newJsonApiLinksCollection(); }
    public function getLink($name) {
        if ($this->links) return $this->links[$name];
        else return null;
    }
    public function getMeta() { return $this->meta; }




    public function setBaseUrl($url) {
        $this->baseUrl = $url;
    }

    public function setData($data) {
        if (!($data instanceof BaseResourceInterface) && !($data instanceof ResourceCollectionInterface)) throw new \InvalidArgumentException("Data must be either a BaseResource or a ResourceCollection");
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




    // TODO: Make it so that jsonSerialize doesn't alter the object (currently it adds a self link to the object if none exists)
    public function jsonSerialize() {
        $data = [];
        if ($this->errors) $data['errors'] = $this->errors;
        else {
            $data['data'] = $this->data;
            if ((!$this->links || !$this->getLink('self')) && $this->data) {
                if ($this->data instanceof BaseResourceInterface) {
                    $this->addLink($this->f->newJsonApiLink([ 'name' => 'self', 'href' => $this->baseUrl.$this->data->getSelfLinkPath() ]));
                } elseif ($this->data instanceof ResourceCollectionInterface && count($this->data)) {
                    $this->addLink($this->f->newJsonApiLink([ 'name' => 'self', 'href' => $this->baseUrl.$this->data[0]->getCollectionLinkPath() ]));
                }
            }
        }

        if ($this->links) $data['links'] = $this->links;
        if ($this->meta) $data['meta'] = $this->meta;

        $data['jsonapi'] = [ 'version' => $this->version, ];

        return $data;
    }
}

