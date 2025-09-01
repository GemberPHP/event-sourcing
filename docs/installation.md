## Installation
Gember Event Sourcing is not tied to any framework (agnostic).
It can be used in any PHP project.

### Symfony
For Symfony (`^7.1`) projects there is a bundle package to ease installation.
Further installation details see [gember/event-sourcing-symfony-bundle](https://github.com/GemberPHP/event-sourcing-symfony-bundle).

### Without a framework
Compatible with any application or framework that supports [container-interop/service-provider](https://github.com/container-interop/service-provider),
there is a universal service provider.
Further installation details see [gember/event-sourcing-universal-service-provider](https://github.com/GemberPHP/event-sourcing-universal-service-provider).

## Dependencies
_Gember Event Sourcing_ relies on multiple external dependencies. To keep flexibility, any external dependency is split from
the core library, and put into a separate dependency package. In this way, each dependency can be replaced by other dependencies
of you likings.

_Gember Event Sourcing_ depends on a few external libraries. To keep things flexible, each of these dependencies is kept separate 
from the core library and moved into its own add-on package. 
That way, you can easily swap out any dependency for another one you prefer.

The following adapter packages are currently available:
- [rdbms-event-store-doctrine-dbal](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal)
- [message-bus-symfony](https://github.com/GemberPHP/message-bus-symfony)
- [identity-generator-symfony](https://github.com/GemberPHP/identity-generator-symfony)
- [file-finder-symfony](https://github.com/GemberPHP/file-finder-symfony)
- [serializer-symfony](https://github.com/GemberPHP/serializer-symfony)

**Note:** When using the Symfony Bundle or the universal service provider, all dependencies packages are installed automatically.
