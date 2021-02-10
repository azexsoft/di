Azexsoft Dependency Injection Change Log
========================================

2.0.0 under development
-----------------------

- Minimal PHP version now is 8.0
- Added PHP 8.0 mixed types
- Changed argument in method `register()` at service provider interface from `Psr\Container\ContainerInterface` to `Azexsoft\Di\Container`
- Fix provider unset instances after rebind deferred service provider
- Remove `1.0.x-dev` branch alias
- `Container` and `Injector` is not final now
- Get concrete binding if abstract binding is string

1.0.3 December 18, 2020
-----------------------

- Class exists check in `has()` method added for autowiring

1.0.2 September 5, 2020
-----------------------

- Object bindings added
- `Injector` added to container construct bindings

1.0.1 September 5, 2020
-----------------------

- Add `1.0.x-dev` branch alias and sort-packages

1.0.0 September 5, 2020
-----------------------

- Initial release.
