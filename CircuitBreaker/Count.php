<?php

declare(strict_types=1);

namespace blog\CircuitBreaker;

use Ackintosh\Ganesha\Storage\StorageKeysInterface;

/**
 * Warning: for now this is not supported by a Redis adapter.
 * TODO: needs adapter support in the compiler pass to be used.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 *
 * @implements \IteratorAggregate<string,null|int|StorageKeysInterface>
 */
final readonly class Count implements \IteratorAggregate
{
    /**
     * @param int|null                  $failureCountThreshold The failure count threshold that changes CircuitBreaker's state to `OPEN`. The count will be increased if `$ganesha->failure()` is called, or will be decreased if `$ganesha->success()` is called.
     * @param int|null                  $intervalToHalfOpen    the interval (seconds) to change CircuitBreaker's state from `OPEN` to `HALF_OPEN`
     * @param StorageKeysInterface|null $storageKeys           the storage adapter instance to store various statistics to detect failures
     */
    public function __construct(
        public ?int $failureCountThreshold = null,
        public ?int $intervalToHalfOpen = null,
        public ?StorageKeysInterface $storageKeys = null,
    ) {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this);
    }
}
