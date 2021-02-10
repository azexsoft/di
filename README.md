Azexsoft Dependency Injection
=============================

Simple [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible
[dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container and injector that is able to
instantiate and configure classes resolving dependencies. Minimal PHP version is 8.0.

Features
--------

- [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible.
- Injector supports constructor (build) and invoke (object method) injection.
- Detects circular references.
- Supports classname, object and Closure bindings
- Supports service providers and deferred service providers.

Configure container
-------------------

Just create container instance with an array of bindings and array of service providers.

```PHP
// Bindings
$bindings = [
    FooInterface::class => Foo::class, // Classname binding
    Baz::class => new Baz(), // Object binding
    
    // Closure binding with DI resolving in Closure params
    Bar::class => fn(\Azexsoft\Di\Injector $injector) => $injector->build( 
        Bar::class,
        [
            'config' => $params['barConfig'],
            'someParameter' => 123
        ]
    ),
];

// Service providers
$providers = [
    ApplicationServiceProvider::class,
];

$container = new \Azexsoft\Di\Container($bindings, $providers);
```

Also, you can add bindings and providers after container be configurated. After rebinding old instance will be removed from
container.

```PHP
$foo = $container->get(FooInterface::class); // will be returned Foo
$container->bind(FooInterface::class, OtherFoo::class);
$otherFoo = $container->get(FooInterface::class); // will be returned OtherFoo
```
