Azexsoft Dependency Injection
=============================

[![License](https://poser.pugx.org/azexsoft/di/license)](https://packagist.org/packages/azexsoft/di)
[![Latest Stable Version](https://poser.pugx.org/azexsoft/di/v)](https://packagist.org/packages/azexsoft/di)
[![Total Downloads](https://poser.pugx.org/azexsoft/di/downloads)](https://packagist.org/packages/azexsoft/di)

Simple [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible
[dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container and injector based on autowiring
that is able to instantiate and configure classes resolving dependencies. Minimal PHP version is 7.4.

Features
--------

- [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible.
- Detects circular references.
- Supports classname, object and Closure bindings.
- Supports service providers and deferred service providers.
- Injector resolves dependencies for definitions when building class and invoke dependencies.
- Injector supports array definitions with method arguments and properties resolving dependencies.

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

Also, you can add bindings and providers after container be configurated. After rebinding old instance will be removed
from container.

```PHP
$foo = $container->get(FooInterface::class); // will be returned Foo
$container->bind(FooInterface::class, OtherFoo::class);
$otherFoo = $container->get(FooInterface::class); // will be returned OtherFoo
```

Array bindings for injector builder.

```PHP
$container->bind(Abstract::class, [
    '__class' => Concrete::class, // Concrete classname
    '__construct()' => [
        'simpleParam' => 123,
        'otherSimpleParam' => $params['concreteSomeParam'],
        'someObject' => new Definition(fn(Dependency $d) => $d->getSomeObject()),
    ],
    'someMethod()' => [
        'methodParam' => $params['someMethodParam'],
    ],
    'someProperty' => 321
]);
```

Instead of:

```PHP
$container->bind(Abstract::class, function(Injector $injector, Dependency $d) use ($params) {
    $concrete = $injector->build(Concrete::class,  [
        'simpleParam' => 123,
        'otherSimpleParam' => $params['concreteSomeParam'],
        'someObject' => $d->getSomeObject(),
    ]);
    $concrete->someMethod($params['someMethodParam']);
    $concrete->someProperty = 321;
    return $concrete;
});
```
