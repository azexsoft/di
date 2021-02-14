Azexsoft Dependency Injection Change Log
========================================

1.1.0 under development
-----------------------

- Bug: Fix provider unset instances after rebind deferred service provider
- Chg: Get concrete binding if abstract binding is string
- Chg: Changed argument in method `register()` at service provider interface from `Psr\Container\ContainerInterface` to `Azexsoft\Di\Container`
- Chg: Remove `1.0.x-dev` branch alias
- Enh #1: Added array definitions for Injector

1.0.3 December 18, 2020
-----------------------

- Enh: Class exists check in `has()` method added for autowiring

1.0.2 September 5, 2020
-----------------------

- Enh: Object bindings added
- Enh: `Injector` added to container construct bindings

1.0.1 September 5, 2020
-----------------------

- Enh: Add `1.0.x-dev` branch alias and sort-packages

1.0.0 September 5, 2020
-----------------------

- Initial release.
