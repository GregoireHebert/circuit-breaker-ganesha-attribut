<?php

declare(strict_types=1);

namespace blog\CircuitBreaker\DependencyInjection\CompilerPass;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder as GaneshaStrategyBuilder;
use Ackintosh\Ganesha\Storage\Adapter\Redis as GaneshaRedisAdapter;
use Ackintosh\Ganesha\Strategy\Count\Builder as GaneshaCountStrategyBuilder;
use Ackintosh\Ganesha\Strategy\Rate\Builder as GaneshaRateStrategyBuilder;
use blog\CircuitBreaker\WithCircuitBreaker;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Cache\DependencyInjection\CachePoolPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 */
final readonly class CircuitBreakerCompilerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $container->register(id: 'ganesha.builder.rate', class: GaneshaRateStrategyBuilder::class)
            ->setFactory(factory: [GaneshaStrategyBuilder::class, 'withRateStrategy']);

        $container->register(id: 'ganesha.builder.count', class: GaneshaCountStrategyBuilder::class)
            ->setFactory(factory: [GaneshaStrategyBuilder::class, 'withCountStrategy']);

        $container->register(id: 'ganesha.adapter.redis', class: GaneshaRedisAdapter::class)
            ->setArgument('$redis', new Reference(CachePoolPass::getServiceProvider($container, $container->getParameter('env(CIRCUIT_BREAKER_REDIS_DSN)'))));

        // TODO add support of other adapters.

        foreach ($container->getDefinitions() as $definition) {
            if ($this->accept($definition) && $reflectionClass = $container->getReflectionClass($definition->getClass(), false)) {
                $this->processClass($definition, $reflectionClass);
            }
        }
    }

    /**
     * Ignore the Attributes, if the container is configured to, or if the class is not autowired.
     */
    private function accept(Definition $definition): bool
    {
        return !$definition->hasTag('container.ignore_attributes') && $definition->isAutowired();
    }

    /**
     * If the class has a constructor, and this one has an argument that needs an HttpClientInterface and
     * this argument has a WithCircuitBreaker attribute on it, then we decorate it with Ganesha HttpClientInterface implementation.
     *
     * @template T of object
     *
     * @param \ReflectionClass<T> $reflectionClass
     */
    private function processClass(Definition $classDefinition, \ReflectionClass $reflectionClass): void
    {
        if (null === $constructor = $reflectionClass->getConstructor()) {
            return;
        }

        foreach ($this->findHttpClientDefinitionArguments($constructor) as ['position' => $position, 'HttpClientReference' => $argumentReference]) {
            $argument = $constructor->getParameters()[$position];

            foreach ($argument->getAttributes(WithCircuitBreaker::class, \ReflectionAttribute::IS_INSTANCEOF) as $k => $attribute) {
                $attribute = $attribute->newInstance();
                $this->processArgument($classDefinition, $argumentReference, $argument->getName(), $attribute);

                if ($k > 0) {
                    throw new AutowiringFailedException('WithCircuitBreaker attribute cannot set more than once on a autowired argument.');
                }
            }
        }
    }

    /**
     * To decorate an argument with Ganesha HttpClientInterface implementation,
     * We create a new Definition of GaneshaHttpClient, inject Ganesha and the original client.
     * If there is a ServiceExtractor, there too, create a Definition and inject the argument into the client.
     *
     * Then, replace the original argument by the new GaneshaHttpClient Definition.
     */
    private function processArgument(Definition $classDefinition, Reference $argumentReference, string $argumentName, WithCircuitBreaker $circuitBreaker): void
    {
        $builder = (new ChildDefinition(parent: \sprintf('ganesha.builder.%s', $circuitBreaker->strategy->value)))
            ->addMethodCall(method: 'adapter', arguments: [new Reference('ganesha.adapter.redis')]);

        foreach ($circuitBreaker->options as $option => $value) {
            if (null === $value) {
                continue;
            }
            $builder->addMethodCall(method: $option, arguments: [$value]);
        }

        $ganeshaHttpClientDefinition = new Definition(class: Ganesha\GaneshaHttpClient::class);
        $ganeshaHttpClientDefinition->setArguments([
            $argumentReference,
            (new Definition(class: Ganesha::class))->setFactory(factory: [$builder, 'build']),
        ]);

        if (null !== $serviceExtractor = $circuitBreaker->serviceNameExtractor) {
            $ganeshaHttpClientDefinition->addArgument((new Definition(class: $serviceExtractor))->setArguments([$argumentName]));
        }

        $classDefinition->setArgument('$'.$argumentName, $ganeshaHttpClientDefinition);
    }

    /**
     * @return iterable<array<string, int|string|Reference>>
     */
    #[ArrayShape([
        'position' => 'int',
        'HttpClientReference' => Reference::class,
    ])]
    private function findHttpClientDefinitionArguments(\ReflectionMethod $constructor): iterable
    {
        foreach ($constructor->getParameters() as $pos => $reflectionParameter) {
            $type = $reflectionParameter->getType();
            if (
                !$type instanceof \ReflectionNamedType
                || !is_a($type->getName(), HttpClientInterface::class, true)
            ) {
                continue;
            }

            yield ['position' => $pos, 'HttpClientReference' => new Reference(HttpClientInterface::class.' $'.$reflectionParameter->getName())];
        }
    }
}
