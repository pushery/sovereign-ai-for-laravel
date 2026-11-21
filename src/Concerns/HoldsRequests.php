<?php

declare(strict_types=1);

namespace Pushery\SovereignAi\Concerns;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Laravel\Ai\Providers\Provider;
use Override;
use Pushery\SovereignAi\Exceptions\EndpointNotAllowed;
use Pushery\SovereignAi\Exceptions\ProviderRequestFailed;
use Pushery\SovereignAi\SovereignAiProvider;

/**
 * The checks around every request of a `laravel/ai` gateway: the base URL is one of the cloud's
 * endpoints, a redirect is not followed, and an error the gateway passes on as a bare HTTP exception
 * names the provider and its cause. A request `laravel/ai` sends without a timeout waits as long as
 * its provider's entry says; one that carries a timeout keeps it.
 *
 * Every request of an OpenAI-compatible gateway gets its client from client(), takes its base URL
 * from baseUrl() and runs inside withErrorHandling(), whose retry and failover read the statuses of
 * an overloaded cloud from overloadedStatusCodes(), so these methods are where it is held.
 */
trait HoldsRequests
{
    /**
     * A 307 or a 308 keeps the method and the body, so a followed redirect would send the prompt to
     * whatever address its `Location` names. The client follows none, and the redirect itself ends
     * the request as an error that withErrorHandling() names, because `laravel/ai` would otherwise
     * read its body as the cloud's answer.
     */
    #[Override]
    protected function client(Provider $provider, ?int $timeout = null): PendingRequest
    {
        $configured = $provider->additionalConfiguration()['timeout'] ?? null;

        return parent::client($provider, $timeout ?? (is_int($configured) && $configured >= 1 ? $configured : null))
            ->withoutRedirecting()
            ->afterResponse(static function (Response $response): Response {
                if ($response->redirect()) {
                    throw new RequestException($response);
                }

                return $response;
            });
    }

    /**
     * @throws EndpointNotAllowed
     */
    #[Override]
    protected function baseUrl(Provider $provider): string
    {
        $url = parent::baseUrl($provider);

        if ($provider instanceof SovereignAiProvider && ! $provider->cloud()->allows($url)) {
            throw EndpointNotAllowed::url($provider->name(), $provider->cloud(), $url);
        }

        return $url;
    }

    /**
     * An error `laravel/ai` passes on as a bare HTTP exception, after its own retry and its own
     * exceptions for a rate limit, an exhausted quota and an overload, names the provider, the
     * status and its cause.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return mixed what the callback returns, of whatever type the caller's callback gives
     *
     * @phpstan-return T
     */
    #[Override]
    protected function withErrorHandling(string $providerName, Closure $callback): mixed
    {
        try {
            return parent::withErrorHandling($providerName, $callback);
        } catch (RequestException $exception) {
            throw ProviderRequestFailed::from($providerName, $exception);
        }
    }

    /**
     * IONOS answers an exhausted capacity with 529, a status `laravel/ai` does not count among the
     * overloaded ones. Counted here, a 529 is retried after a short `Retry-After` and otherwise
     * becomes the exception `laravel/ai` fails over on, as a 503 does.
     *
     * @return list<int>
     */
    #[Override]
    protected function overloadedStatusCodes(): array
    {
        return [529, ...parent::overloadedStatusCodes()];
    }
}
