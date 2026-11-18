<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLoopbackHost
{
    /**
     * Redirect local loopback host variants (127.0.0.1, ::1, localhost)
     * to the canonical host from APP_URL for GET/HEAD requests.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('local')) {
            return $next($request);
        }

        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $appUrl = (string) config('app.url', '');
        $targetHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        if ($targetHost === '') {
            return $next($request);
        }

        $currentHost = strtolower((string) $request->getHost());
        if ($currentHost === $targetHost) {
            return $next($request);
        }

        $loopbackHosts = ['localhost', '127.0.0.1', '::1'];
        if (! in_array($currentHost, $loopbackHosts, true) || ! in_array($targetHost, $loopbackHosts, true)) {
            return $next($request);
        }

        $targetScheme = (string) parse_url($appUrl, PHP_URL_SCHEME);
        if ($targetScheme === '') {
            $targetScheme = $request->getScheme();
        }

        $configuredPort = parse_url($appUrl, PHP_URL_PORT);
        $port = is_int($configuredPort) ? $configuredPort : $request->getPort();
        $isDefaultPort = ($targetScheme === 'http' && $port === 80) || ($targetScheme === 'https' && $port === 443);
        $portPart = $isDefaultPort ? '' : ':'.$port;

        $targetUrl = sprintf(
            '%s://%s%s%s',
            $targetScheme,
            $targetHost,
            $portPart,
            $request->getRequestUri(),
        );

        return redirect()->to($targetUrl, 302);
    }
}
