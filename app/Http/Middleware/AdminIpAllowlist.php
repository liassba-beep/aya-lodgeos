<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isCentralAdminHost($request)) {
            return $next($request);
        }

        $allowlist = collect(explode(',', (string) env('ADMIN_IP_ALLOWLIST', '')))
            ->map(fn (string $entry): string => trim($entry))
            ->filter()
            ->values();

        if ($allowlist->isEmpty()) {
            return $next($request);
        }

        $ip = $request->ip();

        if ($ip && $allowlist->contains(fn (string $entry): bool => $this->matches($ip, $entry))) {
            return $next($request);
        }

        abort(404);
    }

    private function isCentralAdminHost(Request $request): bool
    {
        $host = $request->getHost();
        $centralHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return in_array($host, array_filter([
            $centralHost,
            'app.lodgesos.com',
            'localhost',
            '127.0.0.1',
        ]), true);
    }

    private function matches(string $ip, string $entry): bool
    {
        if (! str_contains($entry, '/')) {
            return $ip === $entry;
        }

        [$range, $bits] = explode('/', $entry, 2);
        $bits = (int) $bits;
        $ipBinary = inet_pton($ip);
        $rangeBinary = inet_pton($range);

        if ($ipBinary === false || $rangeBinary === false || strlen($ipBinary) !== strlen($rangeBinary)) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;
        $maxBits = strlen($ipBinary) * 8;

        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        if ($bytes > 0 && substr($ipBinary, 0, $bytes) !== substr($rangeBinary, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainder)) & 0xff;

        return (ord($ipBinary[$bytes]) & $mask) === (ord($rangeBinary[$bytes]) & $mask);
    }
}
