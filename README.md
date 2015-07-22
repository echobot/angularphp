# AngularPHP

AngularPHP provides a bridge between PHP classes and AngularJS, by automatically exposing selected classes, methods & properties as [AngularJS](https://angularjs.org/) services.  Calls to these exported methods are routed to the same object on the server, with the result being passed back to the caller via a promise.  Every instance of an exported class is automatically converted to and from its equivalent when passing through AngularPHP.

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

`Echobot\AngularPHP\Endpoints\PHPBuiltIn` is an endpoint which understands the standard PHP request representation (`$_SESSION`).  An endpoint for [Laravel](http://laravel.com/) is also included in `Echobot\AngularPHP\Endpoints\Laravel`.

## IDs
AngularPHP allows the specification of one or more properties as containing the object's unique ID.  Any object passing through AngularPHP with a known ID will return a reference to the existing object, allowing one object with a given ID to only exist once in AngularJS.  For example, if you return a user twice from the backend, the second returned value will be a reference to the first, as they share an ID.

## Inheritance
AngularPHP strives to fully respect inheritance, provided the classes in the inheritance chain are also exported.

## Security
Beyond limiting access to exported methods & properties, AngularPHP leaves security __entirely__ up to you.  This is a curse and a blessing - it's won't stop you doing what you want, but it's up to you to secure it.

## Caution!
This code is relatively untested.  We've had a lot of success with it, but one should be prepared for unintended behaviour.  Any bug fixes will be gratefully received.