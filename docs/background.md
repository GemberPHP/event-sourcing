## Background
PHP has some nice libraries available for event sourcing, like [Broadway](https://github.com/broadway), [EventSauce](https://github.com/EventSaucePHP), [Prooph](https://github.com/prooph) and [Ecotone](https://github.com/ecotoneframework).
They all have the "aggregate" as the primary citizen of event sourcing,
with a strict one-to-one relation between a domain event and the aggregate.

In a 'traditional' event sourced application, aggregates have strict boundaries,
meaning that it disallows re-using domain events by multiple different aggregates.
A common rule in event sourcing is that all business logic belonging to an aggregate should reside within that aggregate.

Since aggregates are consistent internally, each behavioral change to an aggregate locks other behavioral changes, 
even if the changes aren’t really related from a domain perspective.

An aggregate as described by Evans in his 'blue book':

> Cluster the ENTITIES and VALUE OBJECTS into AGGREGATES and define boundaries around each.
> Choose one ENTITY to be the root of each AGGREGATE, and control all access to the objects inside the boundary through the root.
> Allow external objects to hold reference to the root only.

_From "Domain-Driven Design: Tackling Complexity in the Heart of Software" by Eric Evans (the 'blue book')._

However, over time, this often leads to "fat aggregates" (the new fat controller? 😛).

Also, the aggregate is a highly technical concept, which makes it difficult to explain to non-technical stakeholders,
e.g. during [EventStorming](https://github.com/ddd-crew/eventstorming-glossary-cheat-sheet) sessions.

## Gember Event Sourcing
Gember addresses these issues and takes a different approach, by using a new concept called "Dynamic Consistency Boundary" (DCB) pattern in mind.

**Gember Event Sourcing is still in the experimental stage, exploring how the DCB pattern works in practice.**

## The Dynamic Consistency Boundary (DCB) pattern
Introduced by Sara Pellegrini in 2023, the DCB pattern rethinks the traditional approach in event sourcing,
which she explains well in her talk: ["The Aggregate is dead. Long live the Aggregate!"](https://sara.event-thinking.io/2023/04/kill-aggregate-chapter-1-I-am-here-to-kill-the-aggregate.html) (a must-read).

**In a nutshell, the DCB pattern removes the strict one-to-one relation between an aggregate and a domain event.**

As a consequence, a domain event can now be reused by multiple different aggregates, or better called now, "business decision models".
This allows us to create business decision models based on a subset of domain events,
or even on domain events from different aggregates, or better called now, "domain tags".

This aligns well with the way EventStorming looks at aggregates nowadays.
What they used to call aggregates are often seen as "systems" or "consistent business rules",
making the problem space more understandable to non-technical stakeholders.

With domain events now sharable across multiple different business decision models, synchronization between aggregates is no longer necessary.
This means that e.g. domain services, which are usually created to manage business logic across aggregates, are not needed anymore.
Similarly, "sagas", when used to subscribe to events in order to synchronize between aggregates, are also no longer necessary.

Replacing "fat aggregates" with multiple business decision models also resolves consistency issues (optimistic locking)
between behavioral changes that seem unrelated from a domain point of view.
A business decision model is consistent only for the specific subset of domain events relevant to its domain tag.

> For example, splitting into different business decision models allows behavioral changes to a course title
> without blocking a student from subscribing to that course (referring to Sara Pellegrini’s example).

Overall, the DCB pattern reduces the "accidental complexity" introduced by aggregates, letting us build software that better reflects the real world.

**More information about the DCB pattern:**

Dynamic Consistency Boundary - Explanation, resources, specification
- Website: https://dcb.events

_"Kill the aggregate!"_ - Sara Pellegrini, Milan Savic, 2023
- Blog: https://sara.event-thinking.io/2023/04/kill-aggregate-chapter-1-I-am-here-to-kill-the-aggregate.html
- Talk: "AxonIQ Con 2023: Kill Aggregate! with Sara Pellegrini & Milan Savic" https://www.youtube.com/watch?v=wXt54BawI-8
- Talk: "Kill Aggregate - Volume 2 - Sara Pellegrini at JOTB25" https://www.youtube.com/watch?v=AQ5fk4D3u9I

_"Rethinking microservices architecture through Dynamic Consistency Boundaries"_ - Bruce Hopkins, 2024
- Blog: https://www.axoniq.io/blog/rethinking-microservices-architecture-through-dynamic-consistency-boundaries
