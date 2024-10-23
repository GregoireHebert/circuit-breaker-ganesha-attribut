<?php

declare(strict_types=1);

namespace blog\CircuitBreaker;

/**
 * Decorates the marked parameter HttpClientInterface {@see HttpClientInterface} with circuit breaker.
 *
 * Usage with "rate" strategy:
 *
 * <code>
 *     #[WithCircuitBreaker(
 *         options: new Rate(
 *              'timeWindow' => 30,
 *              'failureRateThreshold' => 50,
 *              'minimumRequests' => 10,
 *              'intervalToHalfOpen' => 5,
 *              'storageKeys' => new class implement StorageKeysInterface {}
 *         ),
 *         serviceNameExtractor: serviceID
 *     )]
 * </code>
 *
 * Usage with "count" strategy:
 *
 * <code>
 *    #[WithCircuitBreaker(
 *          options: new Count(
 *               'failureRateThreshold' => 50,
 *               'intervalToHalfOpen' => 5,
 *               'storageKeys' => new class implement StorageKeysInterface {}
 *          ),
 *          serviceNameExtractor: serviceID
 *    )]
 * </code>
 *
 * @see https://github.com/ackintosh/ganesha#ganesha-heart-symfony-httpclient
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class WithCircuitBreaker
{
    public CircuitBreakerStrategy $strategy;

    public function __construct(
        public Rate|Count $options,
        public ?string $serviceNameExtractor = null,
    ) {
        $this->strategy = $options instanceof Rate ? CircuitBreakerStrategy::STRATEGY_RATE : CircuitBreakerStrategy::STRATEGY_COUNT;
    }
}
