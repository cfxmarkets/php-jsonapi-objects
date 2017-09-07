<?php
namespace KS\JsonApi;

class GenericResource extends BaseResource implements GenericResourceInterface {
    public function setAttribute($attr, $val) {
        if ($this->validAttributes && !in_array($attr, $this->validAttributes)) throw new \InvalidArgumentException("Invalid attribute passed: This resource has defined a set of valid attributes which does not include `$attr`. Valid attributes are ".implode(', ', $this->validAttributes).".");
        if (!$this->attributes) $this->attributes = [];
        $this->attributes[$attr] = $val;
        $this->validateAttribute($attr);
    }

    public function setRelationship(Relationship $r) {
        if ($this->validRelationships && !in_array($r->getName(), $this->validRelationships)) throw new \InvalidArgumentException("Invalid relationship passed: This resource has defined a set of valid relationships which does not include `{$r->getName()}`. Valid relationships are ".implode(', ', $this->validRelationships).".");
        if (!$this->relationships) $this->relationships = [];
        $this->relationships[$r->getName()] = $r;
        $this->validateRelationship($r->getName());
    }

    public function getAttributes() { return $this->attributes ?: []; }
    public function getAttribute($k) {
        if (!$this->attributes || !array_key_exists($k, $this->attributes)) return null;
        return $this->attributes[$k];
    }
    public function getRelationships() { return $this->relationships ?: []; }
    public function getRelationship($k) {
        if ($this->validRelationships && !in_array($k, $this->validRelationships)) throw new \UnknownRelationshipException("The relationship you've requested, `$k`, is not a valid relationship on this resource.");
        if (!$this->relationships || !array_key_exists($k, $this->relationships)) $this->setRelationship($this->f->newJsonApiRelationship(['name' => $k]));
        return $this->relationships[$k];
    }

    protected function validateRelationship($rel) {}
    protected function validateAttribute($attr) {}
}

