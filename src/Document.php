<?php
namespace CFX\JsonApi;

class Document implements DocumentInterface {
    protected $version = "1.0";

    protected $factory;
    protected $baseUrl;
    protected $data;
    protected $errors;
    protected $links;
    protected $meta;
    protected $included;
    protected $jsonapi;

    public function __construct($data=null) {
        $this->included = $this->getFactory()->newResourceCollection();

        if ($data) {
            if (array_key_exists('errors', $data)) {
                foreach($data['errors'] as $error) {
                    if (!($error instanceof Error)) $error = $this->getFactory()->newError($error);
                    $this->addError($error);
                }
                unset($data['errors']);
            }

            if (array_key_exists('data', $data)) {
                if ($data['data'] === null) $this->data = null;
                elseif ($data['data'] instanceof ResourceInterface || $data['data'] instanceof ResourceCollectionInterface) $this->data = $data['data'];
                elseif (is_array($data['data'])) {
                    $isCollection = is_numeric(implode('', array_keys($data['data'])));
                    if ($isCollection) {
                        $rc = $this->getFactory()->newResourceCollection();
                        foreach ($data['data'] as $r) $rc[] = $this->getFactory()->newResource($r, $r['type']);
                        $this->data = $rc;
                    } else {
                        if (!array_key_exists('type', $data['data'])) throw new \InvalidArgumentException("If you provide a resource via the `data` key, you MUST specify its type via the `data::type` key (e.g., [ 'data' => [ 'type' => 'my-resources', 'attributes' => [ ... ] ] ]).");
                        $this->data = $this->getFactory()->newResource($data['data'], $data['data']['type']);
                    }
                } else {
                    throw new \InvalidArgumentException("Malformed `data` object in initial data array.");
                }
                unset($data['data']);
            }

            if (array_key_exists('links', $data)) {
                if (is_array($data['links'])) {
                    $links = $this->getFactory()->newLinksCollection();
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
                            $links[$name] = $this->getFactory()->newLink($link);
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
                unset($data['links']);
            }

            if (array_key_exists('meta', $data)) {
                if ($data['meta'] instanceof Meta) $this->meta = $data['meta'];
                else $this->meta = $this->getFactory()->newMeta($data['meta']);
                unset($data['meta']);
            }

            if (array_key_exists('included', $data)) {
                if (!is_array($data['included'])) throw new \InvalidArgumentException("If you pass an array of included resources, it must be an array, not an object or string or null or anything else.");
                foreach($data['included'] as $r) $this->included[] = $this->getFactory()->newResource($r, $r['type']);
                unset($data['included']);
            }

            if (array_key_exists('jsonapi', $data)) {
                $this->jsonapi = $data['jsonapi'];
                unset($data['jsonapi']);
            }

            if (count($data) > 0) {
                $e = new MalformedDataException("You have unrecognized data in your JsonApi document. Offending keys are: `".implode('`, `', array_keys($data))."`.");
                $e->addOffender("Document");
                $e->setOffendingData($data);
                throw $e;
            }
        }

        if (!$this->jsonapi) $this->jsonapi = ['version' => $this->version];
    }


    public function getBaseUrl() { return $this->baseUrl; }
    public function getData() { return $this->data; }
    public function getErrors() { return $this->errors ?: $this->getFactory()->newErrorsCollection(); }
    public function getLinks() { return $this->links ?: $this->getFactory()->newLinksCollection(); }
    public function getLink($name) {
        if ($this->links) return $this->links[$name];
        else return null;
    }
    public function getMeta() { return $this->meta; }




    public function setBaseUrl($url) {
        $this->baseUrl = $url;
    }

    public function setData($data) {
        if (!($data instanceof ResourceInterface) && !($data instanceof ResourceCollectionInterface)) throw new \InvalidArgumentException("Data must be either a Resource or a ResourceCollection");
        $this->data = $data;
        return $this;
    }

    public function addError(ErrorInterface $e) {
        if (!$this->errors) $this->errors = $this->getFactory()->newErrorsCollection();
        $this->errors[] = $e;
        return $this;
    }

    public function addLink(LinkInterface $l) {
        if (!$this->links) $this->links = $this->getFactory()->newLinksCollection();
        $this->links[$l->getMemberName()] = $l;
        return $this;
    }

    public function setMeta(MetaInterface $m) {
        $this->meta = $m;
        return $this;
    }

    public function include(ResourceInterface $r)
    {
        foreach ($this->included as $i) {
            if ($i === $r) {
                return $this;
            }
        }

        $this->included[] = $r;
        return $this;
    }

    public function getIncluded(): ResourceCollectionInterface
    {
        return $this->included;
    }




    // TODO: Make it so that jsonSerialize doesn't alter the object (currently it adds a self link to the object if none exists)
    public function jsonSerialize() {
        $data = [];
        if ($this->errors) $data['errors'] = $this->errors;
        else {
            $data['data'] = $this->data;
            if ((!$this->links || !$this->getLink('self')) && $this->data) {
                if ($this->data instanceof ResourceInterface) {
                    $this->addLink($this->getFactory()->newLink([ 'name' => 'self', 'href' => $this->baseUrl.$this->data->getSelfLinkPath() ]));
                } elseif ($this->data instanceof ResourceCollectionInterface && count($this->data)) {
                    $this->addLink($this->getFactory()->newLink([ 'name' => 'self', 'href' => $this->baseUrl.$this->data[0]->getCollectionLinkPath() ]));
                }
            }

            if (count($this->included)) $data["included"] = $this->included;
        }

        if ($this->links) $data['links'] = $this->links;
        if ($this->meta) $data['meta'] = $this->meta;

        $data['jsonapi'] = $this->jsonapi;

        return $data;
    }

    public function getFactory() {
        if (!$this->factory) {
            $this->factory = new Factory();
        }
        return $this->factory;
    }
}

