PHP JSON-API Object Library
==============================================================================

This is a library comprising various classes that implement the [JSON-API Specification](http://jsonapi.org/format) objects in PHP. It is intended to ease the creation of a data system whose resource objects can be serialized to JSON-API.

In this way, it can be used to parse a received JSON-API document into parts that can be validated and persisted, and also to easily transfer data between services that speak JSON-API, such as a REST api.

> 
> **NOTE:** At the time of this writing, almost 100% of development time has gone into the `AbstractResource` class. The rest of the classes exist to the extent that they fulfill the JSON-API spec, but they are not well made or well used.
> 


## Installation

This library can be installed using the standard composer process:

```bash
composer require cfxmarkets/php-jsonapi-objects
```

It complies with [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/), with the `\CFX\JsonApi` namespace found at `src/`.


## Usage

The library is designed to form the foundation of your JSON-API-aware data model (see [Design Philosophy](#design-philosophy) below). Thus, you'll probably use it most commonly to implement resource objects. It is intended that those objects be managed by datasource objects implementing `DatasourceInterface`.

Because this library does not contain any concrete implementations of specific resource objects (excepting the `GenericResource` class, which is not specific enough to provide a useful example), we'll demonstrate usage using a contrived example. Furthermore, because this library doesn't contain persistence logic, we'll forgo discussions of persistence in our example.

Following is an example of how you might use a `User` class as extended from `AbstractResource`:

```php
// Use user info for conditional logic, say on an admin panel
if (!$user->isAtLeast('manager')) {
    throw new UnauthorizedAccessException("You must be at least a manager to view this page");
}

// User user info to prefill forms
$form = '<form method="POST">
    <input type="email" name="email" value="'.$user->getEmail().'">
    <input type="phone" name="phone" value="'.$user->getPhone().'">
    <input type="text" name="name" value="'.$user->getFullName().'">';

if ($user->likes('muffins')) {
    $form .= '
    <h2>Your Muffins</h2>';

    foreach ($user->getMuffins(['orderBy' => 'rating:desc']) as $muffin) {
        $form .= '
    <p>
        <input type="number" name="muffins['.$muffin->getId().'][rating]" value="'.$muffin->getRating().'">
        '.$muffin->getType().'
        <button data-muffin="'.$muffin->getId().'">Remove</button>
    </p>';
}

$form .= '
</form>';

echo $form;

```

```php
// Update user data in response to form input
$user
    ->setName($_POST['name'])
    ->setEmail($_POST['email'])
    ->setPhone($_POST['phone'])
;

$muffinData = $_POST['muffins'];
$muffins = $myBlog->muffins->newCollection();
forach($muffinData as $id => $info) {
    $muffins[] = $myBlog->muffins->get("id=$id")
        ->setRating($info['rating'])
        ->save()
    ;
}

$user
    ->setMuffins($muffins)
    ->save()
;

```

```php
// Update user data from jsonapi input
$user
    ->updateFromData($jsonapiData)
    ->save();
```

The above examples demonstrate a typical way to use objects built on this library. It's worth mentioning, though, that these use-cases don't look any different from objects _not_ built on this library. That's a feature, and speaks to the idea that it should be relatively easy to move objects built on this library off of it.

Back to the examples, though. In many cases, the data was accessed through normal getters and setters. In some cases, however, more complicated methods (like `User::isAtLeast` and `User::likes`, or the modified getter `getMuffins` with an `orderBy` option) were used. The level of abstraction you decide on is ultimately your choice, and this library has no intention of imposing a given philosophy on you. Your choice here should be guided by how tightly bound you want the logic your writing to be to your data. In other words, will you often want to know what a user likes or whether a user is of a minimum role in many parts of your application? If so, then you should build those methods right into your resource class. If not, though -- for example, if you're checking to see if a user has a specific type of muffin in his or her muffins collection -- then you'd do well to use more general getters to compose the logic you'd like.

You should, however, build all methods with a fundamental respect for the underlying data structure that the model is based on.

For example, the `User::isAtLeast` method might utilize a `roles` bitmask maintained in the object and persisted as an integer. That implementation might look something like this:

```php
class User extends \CFX\JsonApi\AbstractResource
{
    protected $attributes = [
        // default to "end-user" role
        'roles' => 1,
        //....
    ];

    protected static function getValidRoles()
    {
        return [
            1 => 'end-user',
            2 => 'advanced-user',
            4 => 'manager',
            8 => 'site-admin',
            16 => 'sys-admin',
        ];
    }

    public function getRoleInteger()
    {
        return $this->_getAttributeValue('roles');
    }

    public function getRoles()
    {
        $roles = [];
        $userRoles = $this->getRoleInteger();
        foreach (static::getValidRoles() as $roleInt => $role) {
            if ($userRoles & $roleInt) {
                $roles[] = $role;
            }
        }
        return $roles;
    }

    public function setRoles($val)
    {
        $roles = 0;
        $validRoles = static::getValidRoles();
        $invalidRoles = [];
        if (is_array($val)) {
            foreach ($val as $role) {
                $roleInt = array_search($role, $validRoles);
                if ($roleInt === false) {
                    $invalidRoles[] = $role;
                } else {
                    $roles += $roleInt;
                }
            }
        } else {
            // Implement logic for validating integer role here....
        }

        if (count($invalidRoles) > 0) {
            $this->setError('roles', 'invalid', [
                "title" => 'Invalid Roles Provided',
                "detail" => "The following roles are invalid: `".implode("`, `", $invalidRoles)."`",
            ]);
        } else {
            $this->clearError('roles', 'invalid');
        }

        return $this->_setAttribute('roles', $roles);
    }

    public function isAtLeast($role)
    {
        $roleLevel = array_search($role, static::getValidRoles(), true);
        if ($roleLevel === false) {
            throw new \RuntimeException("Unrecognized role `$role`. Valid roles are `".implode("`, `", static::getValidRoles())."`.");
        }

        return $this->getRoleInteger() >= $roleLevel);
    }
}

```

With this code, you can persist roles as an integer in the database, but handle them with strings in the program space. This combines the usability and efficiency of named constants with the conciseness of strings.

This represents just a simple example of how to create models with this class. As you can imagine, you can do virtually anything you want with these objects. Enjoy!



## Design Philosophy

Following is a more long-winded discussion of the design philosophy that birthed this library. It doesn't deal so much with the code itself, rather, it attempts to explain the methods available and what problems were being addressed when they were created.


### Problems Addressed

The primary problems this library was addressing where:

1. How to define a resource class that's persistence-enabled without mandating a persistence strategy.
2. How to provide a foundation for data serialization in JSON API while allowing concrete classes to remain only loosely coupled to the foundation.
3. How to interact with the JSON API specification more easily in the program space.

These problems were solved by defining a few relatively lightweight interfaces that comprise an easy approach to model construction and use from a developer-user perspective. Specifically, `ResourceInterface` defines an interface for working with resource objects that are both persistence-enabled and JSON API-conformant; and `DatasourceInterface` defines an interface to be used by a resource object to facilitate persistence.

Read on to learn more about each.


### Resources

The library centers around `AbstractResource` as the basis for a persistence-enabled, JSON API data system. As the name implies, this resource is meant to be extended to the various classes that comprise each system's Data Model. For example, a blogging platform might have the following model classes, all extending from `AbstractResource`:

* `User`
* `Post`
* `Comment`

While the low-level details surrounding datasource interactions and serialization are contained in `AbstractResource` itself, these concrete resource classes will contain their own validation rules and business logic. In fact, it is expected that 96% of all logic contained in derivative resource classes be business logic. There will only be occasional forays into implementation logic to facilitate things like the serialization of complex data or the establishment of "initial state" of complex data. This separation allows for relatively "pure" resource classes (i.e., "model layer") that can easily be adapted to work with other systems.


### Persistence

Interaction with persistence is built in through the defined `DatasourceInterface`. HOWEVER, there is no included implementation of `DatasourceInterface`. This allows us to keep this base package fairly light, while still maintaining compatibility with many different kinds of persistence.

This library's approach to persistence is to define the _way_ we'd like to interact with a persistence mechanism, without defining what that persistence mechansim actually is. Furthermore, we assume here that datasources are _differentiated_ -- that is, that each resource will have its own accompanying resource-specific datasource instance. With that in mind, the included `DatasourceInterface` defines the following public functions:

* `create`
* `newCollection`
* `get`
* `save`
* `delete`
* `convert`
* `getRelated`
* `inflateRelated`
* `initializeResource`
* `getCurrentData`

Some of these methods are fairly obvious, while others require a bit of explanation.

To start with the obvious, `create` and `newCollection` are instantiators: they return a new instance of whatever type of resource the datasource deals in (or a collection thereof). `get` returns a collection or specific resource, depending on the DSL string passed to it (e.g., "id=12345" or "name like '%tom%' and (role & 4)"). `save` either saves a new resource or updates an existing one, depending on whether the passed resource has an id or not. `delete` deletes the given resource.

Now for the more complex methods.


#### `convert`

The data system in this library was designed around the idea that each resource may have several "levels" that represent the same fundamental resource type. For example, the back-end system needs a way to set the "passwordHash" field for a user. This is not a field that we want, for example, API users to be able to set, nor do we want to include our hashing logic and keys in a publicly distributed codebase.

To solve this, we can create a "public" version of the `User` class which stores `password` as a write-only cleartext field, then extend it to create a `Private` version which implements the more complex hashing logic that results in a successful password hash.

Since instantiation is done at the datasource level, it makes sense for the datasource to handle conversions between these related resource types. Thus, `convert` takes a resource and a target "level" and attempts to convert the resource to a resource of the target level while preserving the resource's state. In the `User` example, we might request `$usersDatasource->convert($user, 'private')` to get a "private" version of the given user resource.


#### `getRelated` and `inflateRelated`

Since `DatasourceInterface` was conceived to handle a single resource type, we also need a way to delegate handling of different resource types to other datasources. `getRelated` and `inflateRelated` do that by allowing us the opportunity to call on different datasources according to the name of the relationship being requested. For example, if the `User` object had a `posts` relationship, we would implement logic in `getRelated` and `inflateRelated` that routes those requests to a `PostsDatasource` object.

This of course implies that the `UsersDatasource` _knows_ about a `PostsDatasource`. That problem can be addressed in a number of ways. For example, you might use a `DataContext` to group a number of datasources into a sort of interrelated "directory" (a concept introduced in `cfxmarkets/php-persistence` and that we use at CFX). Or you might build consciousness of the more general data context into each datasource itself using a single common abstract datasource that contains routing and instantiation logic for the various types of sibling datasources that your application must deal with.

Regardless, `getRelated` and `inflateRelated` are two methods that allow you to delegate calls for related resources to other datasources.


#### `initializeResource`

It's often the case that you end up with a resource that is uninitialized. This happens, for example, when you get a relationship, which has only an ID. In such a case, you can call on the datasource to initialize the resource, which basically just gets the resource from persistence, then uses the returned data together with the resource's `restoreFromData` method to inflate the object.

That brings us to our final method, `getCurrentData`


#### `getCurrentData`

The `getCurrentData` method is a special method design to couple the public `Datasource` object and the public `Resource` object with a private handshake. It was conceived to work with the `AbstractResource` class's `restoreFromData` like so:

1. `Datasource` gets data from whatever its persistence source is and makes sure it's in JSON API format.
2. `Datasource` places that data in its protected `currentData` property.
3. `Datasource` either calls `Resource`'s `restoreFromData` method or instantiates a new resource (which implicitly calls this method).
4. `Resource` calls `Datasource`'s `getCurrentData` method, which should return the prepared data and then set `currentData` back to null.
5. `Resource` updates its fields internally using this "trusted" data from `Datasource`. 

