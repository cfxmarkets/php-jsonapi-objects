<?php
namespace KS\JsonApi;

class GenericResource extends BaseResource implements GenericResourceInterface {
    protected $validAttributes;
    protected $validRelationships;

    public function __construct(FactoryInterface $f, $props=null, $validAttributes=null, $validRelationships=null) {
        if ($validAttributes) $this->validAttributes = $validAttributes;
        if ($validRelationships) $this->validRelationships = $validRelationships;
        return parent::__construct($f, $props);
    }

    public static function restoreFromData(FactoryInterface $f, $data, $validAttributes=null, $validRelationships=null) {
        $obj = new static($f, $data, $validAttributes, $validRelationships);
        $obj->initialized = true;
        return $obj;
    }
        

    protected function initializeAttributes($initialAttrs=[]) {
        // Merge attributes
        $attrs = $this->attributes;
        foreach($initialAttrs as $a => $v) $attrs[$a] = $v;

        // Iterate through attributes and set
        foreach($attrs as $k => $v) {
            if (!is_string($k)) throw new \RuntimeException("You've passed an attribute with a non-string index (`$k` => `$v`). Attributes must be string-indexed, including default attributes. Example: `protected \$attributes = [ 'name' => null, 'dob' => null ]");
            $this->setAttribute($k, $v);
        }
    }

    protected function initializeRelationships($initialRels=[]) {
        // Add missing required relationships as empty relationships
        $rels = [];
        foreach($this->relationships as $r) {
            if (!is_string($r)) throw new \RuntimeException("You may only initialize relationships with an array of relationship names, since relationships may not have default values");
            $this->rels[$r] = [ 'data' => null ];
        }

        foreach($initialRels as $n => $r) $rels[$n] = $r;

        // Iterate through relationships and set
        foreach($rels as $k => $v) {
            // Convert to relationship, if necessary
            if (!($v instanceof RelationshipInterface)) {
                $v['name'] = $k;
                $v = $this->f->newJsonApiRelationship($v);
            }
            $this->setRelationship($v);
        }
    }

    public function setAttribute($attr, $val) {
        if ($this->validAttributes && !in_array($attr, $this->validAttributes)) throw new \InvalidArgumentException("Invalid attribute passed: This resource has defined a set of valid attributes which does not include `$attr`. Valid attributes are ".implode(', ', $this->validAttributes).".");
        if (!$this->attributes) $this->attributes = [];
        $this->attributes[$attr] = $val;
        $this->validateAttribute($attr);
    }

    public function setRelationship(RelationshipInterface $r) {
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
        if ($this->validRelationships && !in_array($k, $this->validRelationships)) throw new UnknownRelationshipException("The relationship you've requested, `$k`, is not a valid relationship on this resource.");
        if (!$this->relationships || !array_key_exists($k, $this->relationships)) $this->setRelationship($this->f->newJsonApiRelationship(['name' => $k]));
        return $this->relationships[$k];
    }

    protected function validateRelationship($rel) {}
    protected function validateAttribute($attr) {}
}

