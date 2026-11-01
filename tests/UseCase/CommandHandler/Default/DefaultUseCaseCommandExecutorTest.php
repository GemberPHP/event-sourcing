<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\UseCase\CommandHandler\Default;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Repository\InvalidUseCaseDomainTagsException;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\Attribute\AttributeCommandHandlerResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\DefaultUseCaseResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\Attribute\AttributeEventSubscriberResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\Snapshot\Attribute\AttributeSnapshotResolver;
use Gember\EventSourcing\Test\TestDoubles\Repository\TestUseCaseRepository;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestMultipleDomainTagsCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithMultipleDomainTags;
use Gember\EventSourcing\UseCase\CommandHandler\Default\DefaultUseCaseCommandExecutor;
use Gember\EventSourcing\UseCase\UseCaseAttributeRegistry;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class DefaultUseCaseCommandExecutorTest extends TestCase
{
    private TestUseCaseRepository $useCaseRepository;
    private DefaultUseCaseCommandExecutor $executor;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        UseCaseAttributeRegistry::initialize(
            new DefaultUseCaseResolver(
                new AttributeDomainTagResolver($attributeResolver = new ReflectorAttributeResolver()),
                new AttributeCommandHandlerResolver($attributeResolver),
                new AttributeEventSubscriberResolver($attributeResolver),
                new AttributeSnapshotResolver($attributeResolver),
            ),
        );

        $this->executor = new DefaultUseCaseCommandExecutor(
            $this->useCaseRepository = new TestUseCaseRepository(),
        );
    }

    #[Test]
    public function itShouldSaveUseCaseWhenDomainTagsMatchCommandInAnyOrder(): void
    {
        $this->executor->execute(
            new TestMultipleDomainTagsCommand('tag-a', 'tag-b'),
            new CommandHandlerDefinition(TestMultipleDomainTagsCommand::class, '__invoke', CreationPolicy::IfMissing),
            TestUseCaseWithMultipleDomainTags::class,
            '__invoke',
            'tag-b',
            'tag-a',
        );

        self::assertTrue($this->useCaseRepository->has(TestUseCaseWithMultipleDomainTags::class, 'tag-a', 'tag-b'));
    }

    #[Test]
    public function itShouldFailWhenDomainTagsOfCreatedUseCaseDoNotMatchCommand(): void
    {
        self::expectException(InvalidUseCaseDomainTagsException::class);
        self::expectExceptionMessage('[tag-a, tag-c] do not match the domain tags of the command [tag-a, tag-b]');

        $this->executor->execute(
            new TestMultipleDomainTagsCommand('tag-a', 'tag-c'),
            new CommandHandlerDefinition(TestMultipleDomainTagsCommand::class, '__invoke', CreationPolicy::IfMissing),
            TestUseCaseWithMultipleDomainTags::class,
            '__invoke',
            'tag-a',
            'tag-b',
        );
    }

    #[Test]
    public function itShouldFailWhenDomainTagsOfLoadedUseCaseDoNotMatchCommand(): void
    {
        $useCase = new TestUseCaseWithMultipleDomainTags();
        $useCase->firstTag = 'tag-a';
        $useCase->secondTag = 'tag-b';

        $this->useCaseRepository->save($useCase);

        self::expectException(InvalidUseCaseDomainTagsException::class);

        $this->executor->execute(
            new TestMultipleDomainTagsCommand('tag-a', 'tag-c'),
            new CommandHandlerDefinition(TestMultipleDomainTagsCommand::class, '__invoke', CreationPolicy::Never),
            TestUseCaseWithMultipleDomainTags::class,
            '__invoke',
            'tag-a',
            'tag-b',
        );
    }

    #[Test]
    public function itShouldNotSaveUseCaseWhenDomainTagsDoNotMatchCommand(): void
    {
        try {
            $this->executor->execute(
                new TestMultipleDomainTagsCommand('tag-a', 'tag-c'),
                new CommandHandlerDefinition(TestMultipleDomainTagsCommand::class, '__invoke', CreationPolicy::IfMissing),
                TestUseCaseWithMultipleDomainTags::class,
                '__invoke',
                'tag-a',
                'tag-b',
            );
        } catch (InvalidUseCaseDomainTagsException) {
        }

        self::assertFalse($this->useCaseRepository->has(TestUseCaseWithMultipleDomainTags::class, 'tag-a', 'tag-c'));
    }
}
