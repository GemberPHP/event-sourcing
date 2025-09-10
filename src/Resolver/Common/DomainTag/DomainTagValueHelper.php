<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\DomainTag;

final readonly class DomainTagValueHelper
{
    /**
     * @param list<DomainTagDefinition> $domainTagDefinitions
     *
     * @return list<string>
     */
    public static function getDomainTagValues(object $class, array $domainTagDefinitions): array
    {
        $values = [];
        foreach ($domainTagDefinitions as $domainTagDefinition) {
            if ($domainTagDefinition->type === DomainTagType::Method) {
                $values = array_values(array_map(
                    fn($domainTag) => (string) $domainTag,
                    $class->{$domainTagDefinition->domainTagName}(),
                ));

                break;
            }

            $values[] = (string) $class->{$domainTagDefinition->domainTagName};
        }

        return $values;
    }
}
