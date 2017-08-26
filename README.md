PHP JSON-API Object Library
==============================================================================

This is a library comprising various objects that implement the [JSON-API Specification](http://jsonapi.org/format) objects in PHP. It is intended to ease the manipulation of data objects that conform to the ideas of the JSON-API format (that is, "data as objects with attributes and relationships").

In this way, it can be used to parse a received JSON-API document into parts that can be validated and persisted, and also to easily transfer data from persistence to an API client across the network via JSON-API strings.

It is intended to be extended, such that each specific type of Resource may have its own validation rules and business logic.

It is also intended that interactions with a persistence layer be applied via Traits, as this functionality is not built-in but is anticipated.

## Examples

To load a JSON-API document into program object form,

```php
$doc = new \KS\JsonApi\Document(json_decode($jsonString, true));
```

This will yield a number of children in accordance with the JSON-API specification: `errors`, `data`, `links`, `jsonapi`, `meta`, and `included`. (Many of these are not yet fully implemented, so mileage will vary.)

Eventually, it would be useful to turn the `Document` object into a more user-friendly object. For now, though, it remains a fairly faithful and static object representation of the JSON that was passed in.

To continue the above example, I might do this:

```php
$doc = new \KS\JsonApi\Document(json_decode($jsonString, true));

if (count($doc->getErrors()) > 0) throw new \RuntimeException($doc->getErrors()->summarize());

$data = $doc->getData();

if ($data instanceof \KS\JsonApi\CollectionInterface) {
    foreach($data as $item) {
        // Do something with item
    }
} else {
    $item = $data;
    if ($item->getAttribute('myAttr') == true) {
        // Do something with item
    } else {
        // Do something else with item
    }
}
```

Note that in the above example, instead of using the `getAttribute` method, we might choose to create a derivative object, `ItemResource`, that has a `getMyAttr` method.

## Future

Much more documentation and development still to come....

