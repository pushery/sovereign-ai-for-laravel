<?php

declare(strict_types=1);

namespace Pushery\SovereignAi\Exceptions;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Laravel\Ai\Exceptions\AiException;

/**
 * A cloud answered a request with an error that `laravel/ai` passes on as a bare HTTP exception,
 * such as a rejected key, an unknown model or a refused request, or with a redirect, which is not
 * followed. This names the provider, the status, what the status means and what the cloud said.
 *
 * A rate limit, an exhausted quota and an overloaded cloud keep the exceptions `laravel/ai` throws
 * for them, because those are the ones its failover between providers acts on.
 */
final class ProviderRequestFailed extends AiException
{
    /**
     * The longest message of a cloud a message of this exception repeats.
     */
    private const int MESSAGE_LENGTH = 300;

    public static function from(string $provider, RequestException $exception): self
    {
        $status = $exception->response->status();

        return new self(sprintf(
            'The AI provider [%s] answered %d: %s.%s',
            $provider,
            $status,
            self::cause($exception->response),
            self::said($exception),
        ), $status, $exception);
    }

    private static function cause(Response $response): string
    {
        if ($response->redirect()) {
            return 'the endpoint redirects the request, and a redirect is not followed';
        }

        return match ($response->status()) {
            401 => 'the key is missing or was rejected',
            403 => 'the key was refused for this request',
            404 => 'the model or the path is unknown',
            400, 422 => 'the request was refused',
            413 => 'the request is too large',
            default => 'the request failed',
        };
    }

    /**
     * What the cloud wrote about the error, in the first of the shapes the clouds use that the
     * response has: the OpenAI shape `error.message`, a `detail`, a top-level `message`, or the
     * IONOS shape `messages[0].message`.
     */
    private static function said(RequestException $exception): string
    {
        $response = $exception->response;

        foreach (['error.message', 'detail', 'message', 'messages.0.message'] as $path) {
            $message = $response->json($path);

            if (is_string($message) && trim($message) !== '') {
                return sprintf(' The provider said: %s', Str::limit(trim($message), self::MESSAGE_LENGTH));
            }
        }

        return '';
    }
}
