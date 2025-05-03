# 🫚 Gember Event sourcing
[![Build Status](https://scrutinizer-ci.com/g/GemberPHP/event-sourcing/badges/build.png?b=main)](https://github.com/GemberPHP/event-sourcing/actions)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/GemberPHP/event-sourcing.svg?style=flat)](https://scrutinizer-ci.com/g/GemberPHP/event-sourcing/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/GemberPHP/event-sourcing.svg?style=flat)](https://scrutinizer-ci.com/g/GemberPHP/event-sourcing)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-8892BF.svg?style=flat)](http://www.php.net)

_Use case driven EventSourcing - Let go of the Aggregate with the Dynamic Consistency Boundary (DCB) pattern._

## Documentation

- [Background](/docs/background.md)
- [Installation](/docs/installation.md)
- [Usage](/docs/usage.md)
- Library architecture
- Library reference
- Hooking into the library

## In a nutshell

#### Traditional 'Aggregate driven' EventSourcing

Domain concepts are modeled towards objects: the aggregate.

- Any business logic related to a single domain object should live inside the aggregate
- Logic that involves other domain objects or groups of the same kind of domain objects does not belong in the aggregate

<img width="1262" alt="aggregate-driven-event-sourcing" src="/docs/images/aggregate-driven-event-sourcing.png" />

#### 'Use case driven' EventSourcing
Domain concepts are modeled through use cases.

- Any business logic tied to a use case should live inside that use case
- A use case can relate to one or more domain concepts

<img width="495" alt="use-case-driven-event-sourcing" src="/docs/images/use-case-driven-event-sourcing.png" />