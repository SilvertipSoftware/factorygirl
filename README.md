# SilvertipSoftware/FactoryGirl

FactoryGirl is a (or hopes to be) a relatively faithful port of [thoughtbot/factory_girl](https://github.com/thoughtbot/factory_girl), a Ruby-land object factory library for testing.


## Installation

### Composer

Add `silvertipsoftware/factorygirl` to the `require-dev` section of your `composer.json`:

```json
"require-dev": {
    "silvertipsoftware/factorygirl": "dev-master"
}
```

You can obviously choose any available version(s). Run `composer update` to get it.

### Laravel

Add the FactoryGirl ServiceProvider to your Laravel application:

```php
'providers' => array(
    ...
    'SilvertipSoftware\FactoryGirl\FactoryGirlServiceProvider',
    ...
),
```

(optional) Add the following snippets to your `app/config/app.php` file:

```php
'aliases' => array(
    ...
    'Factory' => 'SilvertipSoftware\FactoryGirl',
    ...
),
```


## Supported PHP Versions

Currently, PHP 5.3+ is supported. PHP 5.4 would make some things nicer, so that may change for future releases.


## Documentation

See `FactoryGirl` documentation to get a feel for what it does and is used for. The syntax is hopefully a straightforward port to PHP.

### Factory Definitions

The `app/tests/factories.php` file is used to store the factory definitions, and is automatically loaded on the first build/create on an object.

### Basic Factory

```php
Factory::define('room', function($f) {
    return array(
        'name' => 'Meeting Room',
        'capacity' => 5,
        'notes' => 'Great views'
    });
```

defines a factory for a `Room` Eloquent model, and sets the `name`,`capacity`, and `notes` attributes to the given values.

### Building/Creating Objects

With the factory defined above, your test code can do:

```php
$room = Factory::build('room');
```

to get a new room instance, which is not saved to the database. If you want it persisted, use `create`:

```php
$room = Factory::create('room');
```

In either case, attributes may be overridden by passing an array as a second parameter:

```php
$large_room = Factory::create('room', array(
    'capacity' => 100
));
```

### Sequences

Sequences return a value based on a increasing index passed to the closure. Useful for creating uni
que attributes in a standard way.

```php
Factory::sequence('email', function($n) {
    return 'noreply'.$n'.@somedomain.com';
}
```

Then, in a factory, you can use the sequence by:

```php
Factory::define('user', function($f) {
    'username' => 'Joe Public',
    'email' => $f->next('email'),
    'status' => 'active'
});
```

### Associations

Given a factory for an `account` model, you can associate a `user` model to it with:

```php
Factory::define('user', function($f) {
    return array(
        'username' => 'Joe Public',
        'email' => $f->next(),
        'account' => $f->associate()
    );
});
```

When a `user` is built, an `account` instance will be created and the `account_id` of `user` will be set to reference the new `account`. Currently, only `belongsTo` relations are supported, and the only "build strategy" is to save the associated object in the database.

Attribute values in the associated object can be overridden by passing an array:

```php
        ...
        'account' => $f->associate( array(
            'plan' => 'platinum'
        )) 
        ...
```

### Closures as Attributes

A closure can also be passed as an attribute value, and it is evaluated during model build. This lets you do more complex logic at build-time. This is particularly useful for 3-object associations. For example:

```php
Factory::define('room', function($f) {
    return array(
        'name' => 'Meeting Room',
        'account' => $f->associate(),
        'location' => function($room,$f) {
            return $f->associate( array(
                'account' => $room['account']
            );
        }
    );
});
```
 
Attribute values are evaluated in the order they are given in the factory definition, so swapping `account` and `location` above would not have worked.

