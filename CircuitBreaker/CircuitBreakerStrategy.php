<?php

declare(strict_types=1);

namespace blog\CircuitBreaker;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @internal
 */
enum CircuitBreakerStrategy: string
{
    case STRATEGY_RATE = 'rate';
    case STRATEGY_COUNT = 'count';
}
