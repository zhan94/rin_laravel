<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Localization\LocalizedText;
use Closure;
use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetRequestedLocale
{
    public function __construct(
        private readonly QueueingFactory $cookies,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        $request->setLocale($locale);

        $response = $next($request);

        $queryParameter = $this->queryParameterName();
        if ($request->query($queryParameter) !== null) {
            $this->cookies->queue(
                cookie(
                    name: $this->cookieName(),
                    value: $locale,
                    minutes: 60 * 24 * 365,
                    httpOnly: false,
                    sameSite: 'lax',
                ),
            );
        }

        return $response;
    }

    private function resolveLocale(Request $request): string
    {
        $supported = LocalizedText::supportedLocales();
        $queryParameter = $this->queryParameterName();
        $cookieName = $this->cookieName();

        foreach ([
            $request->query($queryParameter),
            $request->cookie($cookieName),
            $request->header('X-Locale'),
        ] as $candidate) {
            $resolved = $this->matchLocale($candidate, $supported);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return $this->matchLocale(config('localization.default_locale'), $supported)
            ?? LocalizedText::fallbackLocale();
    }

    /**
     * @param  list<string>  $supported
     */
    private function matchLocale(mixed $value, array $supported): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $parts = preg_split('/,/', strtolower($value)) ?: [];

        foreach ($parts as $part) {
            $token = trim((string) preg_replace('/;.*$/', '', $part));
            if ($token === '') {
                continue;
            }

            if (in_array($token, $supported, true)) {
                return $token;
            }

            $primary = explode('-', $token)[0];
            if (in_array($primary, $supported, true)) {
                return $primary;
            }
        }

        return null;
    }

    /**
     * @return non-empty-string
     */
    private function queryParameterName(): string
    {
        $queryParameter = trim((string) config('localization.query_parameter', 'lang'));

        return $queryParameter !== '' ? $queryParameter : 'lang';
    }

    /**
     * @return non-empty-string
     */
    private function cookieName(): string
    {
        $cookieName = trim((string) config('localization.cookie_name', 'app_locale'));

        return $cookieName !== '' ? $cookieName : 'app_locale';
    }
}
