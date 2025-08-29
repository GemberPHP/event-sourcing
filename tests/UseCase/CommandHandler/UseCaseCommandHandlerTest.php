<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\UseCase\CommandHandler;

use Gember\EventSourcing\Registry\CommandHandler\Reflector\ReflectorCommandHandlerRegistry;
use Gember\EventSourcing\Repository\UseCaseNotFoundException;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\Attribute\AttributeDomainTagsResolver;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\Attribute\AttributeCommandHandlersResolver;
use Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\Attribute\AttributeDomainTagsPropertiesResolver;
use Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent\Attribute\AttributeSubscriberMethodForEventResolver;
use Gember\EventSourcing\Test\TestDoubles\Repository\TestUseCaseRepository;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestSecondUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommandHandler;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Finder\TestFinder;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Reflector\TestReflector;
use Gember\EventSourcing\UseCase\CommandHandler\UseCaseCommandHandler;
use Gember\EventSourcing\UseCase\UseCaseAttributeRegistry;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class UseCaseCommandHandlerTest extends TestCase
{
    private TestUseCaseRepository $useCaseRepository;
    private UseCaseCommandHandler $handler;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        UseCaseAttributeRegistry::initialize(
            new AttributeDomainTagsPropertiesResolver($attributeResolver = new ReflectorAttributeResolver()),
            new AttributeSubscriberMethodForEventResolver($attributeResolver),
        );

        $finder = new TestFinder();
        $finder->files = [
            'path/to/use-case.php',
        ];

        $reflector = new TestReflector();
        $reflector->files = [
            'path/to/use-case.php' => TestUseCaseWithCommandHandler::class,
        ];

        $this->handler = new UseCaseCommandHandler(
            $this->useCaseRepository = new TestUseCaseRepository(),
            new ReflectorCommandHandlerRegistry(
                $finder,
                $reflector,
                new AttributeCommandHandlersResolver(new ReflectorAttributeResolver()),
                'path',
            ),
            new AttributeDomainTagsResolver(
                $attributeResolver,
            ),
        );
    }

    #[Test]
    public function itShouldHandleCommand(): void
    {
        $useCase = new TestUseCaseWithCommandHandler();
        $useCase->domainTag = 'df0cb0be-ee4a-4d08-af50-f5ad73466337';

        $this->useCaseRepository->save($useCase);

        $this->handler->__invoke(new TestUseCaseWithCommand('df0cb0be-ee4a-4d08-af50-f5ad73466337'));

        self::assertSame([
            TestUseCaseWithCommandHandler::class . '::__invoke',
        ], $useCase->isCalled);
    }

    #[Test]
    public function itShouldFailWhenUseCaseIsMissingAndCreationPolicyIsNever(): void
    {
        self::expectException(UseCaseNotFoundException::class);

        $this->handler->__invoke(new TestUseCaseWithCommand('1c134ff5-961a-4e0b-abba-74460b550599'));

        self::assertFalse($this->useCaseRepository->has(TestUseCaseWithCommandHandler::class, '1c134ff5-961a-4e0b-abba-74460b550599'));
    }

    #[Test]
    public function itShouldCreateUseCaseWhenUseCaseIsMissingAndCreationPolicyIsIfMissing(): void
    {
        $this->handler->__invoke(new TestSecondUseCaseWithCommand('f491b1fc-6ae1-4e7c-8b68-0e831249630c'));

        self::assertTrue($this->useCaseRepository->has(TestUseCaseWithCommandHandler::class, 'f491b1fc-6ae1-4e7c-8b68-0e831249630c'));

        $useCase = $this->useCaseRepository->get(TestUseCaseWithCommandHandler::class, 'f491b1fc-6ae1-4e7c-8b68-0e831249630c');

        self::assertSame([
            TestUseCaseWithCommandHandler::class . '::second',
        ], $useCase->isCalled);
    }
}
