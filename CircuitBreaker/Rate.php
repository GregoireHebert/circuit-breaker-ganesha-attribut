<?php

declare(strict_types=1);

namespace blog\CircuitBreaker;

use Ackintosh\Ganesha\Storage\StorageKeysInterface;

/**
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @implements \IteratorAggregate<string,null|int|StorageKeysInterface>
 */
final readonly class Rate implements \IteratorAggregate
{
    /**
     * @param int|null                  $timeWindow           the interval in time (seconds) that evaluate the thresholds
     * @param int|null                  $failureRateThreshold the failure rate threshold in percentage that changes CircuitBreaker's state to `OPEN`
     * @param int|null                  $minimumRequests      even if `failureRateThreshold` exceeds the threshold, CircuitBreaker remains in `CLOSED` if `minimumRequests` is below this threshold
     * @param int|null                  $intervalToHalfOpen   the interval (seconds) to change CircuitBreaker's state from `OPEN` to `HALF_OPEN`
     * @param StorageKeysInterface|null $storageKeys          the storage adapter instance to store various statistics to detect failures
     */
    public function __construct(
        public ?int $timeWindow = null,
        public ?int $failureRateThreshold = null,
        public ?int $minimumRequests = null,
        public ?int $intervalToHalfOpen = null,
        public ?StorageKeysInterface $storageKeys = null,
    ) {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this);
    }
}
