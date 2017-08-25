JSON-API Object Library
==============================================================================

This is a library comprising various objects that implement the [JSON-API Specification](http://jsonapi.org/format) in PHP. It is intended to ease the manipulation of objects that are represented in a JSON-API string, or that are serializable to a JSON-API string.

In this way, it can be used to parse a received JSON-API document into parts that can be validated and persisted, and also to easily transfer data from persistence to an API client across the network via JSON-API.

It is intended to be extended, such that each specific type of Resource may have its own validation rules and business logic.

It is also intended that interactions with a persistence layer be applied via Traits, as this functionality is not built-in.

Much more documentation and development still to come.

