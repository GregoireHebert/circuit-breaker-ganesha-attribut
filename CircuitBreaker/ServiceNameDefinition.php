<?php

declare(strict_types=1);

namespace blog\CircuitBreaker;

use Ackintosh\Ganesha\HttpClient\ServiceNameExtractorInterface;

/**
 * GaneshaHttpClient do not support Scoped Clients. This is to solve the issue.
 *
 * @author Grégoire Hébert <contact@gheb.dev>
 */
final readonly class ServiceNameDefinition implements ServiceNameExtractorInterface
{
    public function __construct(private string $serviceName)
    {
    }

    #[\Override]
    public function extract(string $method, string $url, array $requestOptions = []): string
    {
        // We treat the combination of host name and HTTP method name as $service.
        return sprintf('%s.%s_%s', $this->serviceName, $url, $method);
    }
}
