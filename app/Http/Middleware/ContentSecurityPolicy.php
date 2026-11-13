<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ajoute l'en-tête Content-Security-Policy aux réponses web.
 *
 * Un nonce est généré à chaque requête et transmis à Vite : les balises
 * produites par @vite et les <script @nonce> des vues le portent.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('csp.enabled')) {
            return $next($request);
        }

        $nonce = Vite::useCspNonce(Str::random(32));

        $response = $next($request);

        // La page d'exception de Laravel (APP_DEBUG) repose sur des scripts inline
        if (config('app.debug') && property_exists($response, 'exception') && $response->exception) {
            return $response;
        }

        $header = config('csp.report_only')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        if (! $response->headers->has($header)) {
            $response->headers->set($header, $this->policy($nonce));
        }

        return $response;
    }

    public function policy(string $nonce): string
    {
        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ];

        // Serveur de développement Vite (npm run dev)
        if (Vite::isRunningHot()) {
            $hot = rtrim((string) file_get_contents(public_path('hot')));
            $ws = preg_replace('#^http#', 'ws', $hot);
            foreach (['script-src', 'style-src', 'img-src', 'font-src'] as $directive) {
                $directives[$directive][] = $hot;
            }
            $directives['connect-src'][] = $hot;
            $directives['connect-src'][] = $ws;
        }

        foreach (config('csp.extra', []) as $directive => $sources) {
            if (! empty($sources) && isset($directives[$directive])) {
                $directives[$directive] = array_merge(
                    $directives[$directive],
                    preg_split('/\s+/', trim($sources))
                );
            }
        }

        $policy = [];
        foreach ($directives as $directive => $sources) {
            $policy[] = $directive . ' ' . implode(' ', array_unique($sources));
        }

        if ($uri = config('csp.report_uri')) {
            $policy[] = 'report-uri ' . $uri;
        }

        return implode('; ', $policy);
    }
}
