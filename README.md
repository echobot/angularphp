# AngularPHP

AngularPHP provides a bridge between PHP classes and AngularJS, by automatically exposing selected classes, methods & properties as [AngularJS](https://angularjs.org/) services.  Calls to these exported methods are routed to the same object on the server, with the result being passed back to the caller via a promise.  Every instance of an exported class is automatically converted to and from its equivalent when passing through AngularPHP.

## Installation

Install via Composer:

```sh
composer require echobot/angularphp
```

## Usage

In your project framework of choice, set up a route which passes the current request to AngularPHP and which renders the response.  To assist in this, two utility classes exist which achieve this:

### [Laravel](http://laravel.com/)

The `Echobot\AngularPHP\Endpoints\Laravel` provides a Laravel-compatible endpoint, which can be easily used to create an AngularPHP endpoint on a particular route:

```php
Echobot\AngularPHP\Endpoints\Laravel::create('/models.js')
        ->addDirectory(__DIR__ . '/models/')
        ->debug($debug);
```

That snippet serves up all exported classes in the `models` directory, from the route `/models.js`.

### PHP

The `Echobot\AngularPHP\Endpoints\PHPBuiltIn` class can be used to serve requests based on the `$_SERVER` global variable, which includes most old frameworks and PHP's built-in command-line web server.

## Example

The following example defines an AngularPHP endpoint which exposes a single class (`ExampleClass`) in an AngularJS module called `ExampleModule`:

```php
class ExampleClass
{
    /** @Export */
    public $property;
    
    /** @Export */
    public function someMethod($arg)
    {
        return 'Here is your argument: ' . $arg;
    }
}

Echobot\AngularPHP\Endpoints\PHPBuiltIn::create()
    ->setModuleName('ExampleModule')
    ->addClass('ExampleClass')
    ->run();
```

`Echobot\AngularPHP\Endpoints\PHPBuiltIn` is an endpoint which understands the standard PHP request representation (`$_SERVER`).  An endpoint for [Laravel](http://laravel.com/) is also included in .

## IDs
AngularPHP allows the specification of one or more properties as containing the object's unique ID by adding `@Id` to the property's DocComment.  Any object passing through AngularPHP with a known ID will return a reference to the existing object, allowing one object with a given ID to only exist once in AngularJS.  For example, if you return a user twice from the backend, the second returned value will be a reference to the first, as they share an ID.

## Read-Only Properties
By adding `@ReadOnly` to the DocComment of an exported property, the value of this property will not be passed back from the AngularJS module, and will not be updated on the specified object/class.

## Inheritance
AngularPHP strives to fully respect inheritance, provided the classes in the inheritance chain are also exported.

## Security
Beyond limiting access to exported methods & properties, AngularPHP leaves security __entirely__ up to you.  This is a curse and a blessing - it's won't stop you doing what you want, but it's up to you to secure it.

## Caution!
This code is relatively untested.  We've had a lot of success with it, but one should be prepared for unintended behaviour.  Any bug fixes will be gratefully received.