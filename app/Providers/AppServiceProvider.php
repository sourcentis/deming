<?php

namespace App\Providers;

use DB;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use PhpOffice\PhpWord\Settings as PhpWordSettings;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use LdapRecord\Container;
use Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();

        // Markdown affiché dans les vues : le HTML brut est échappé et les liens
        // dangereux (javascript:, data:...) sont neutralisés
        \Parsedown::instance()->setSafeMode(true);

        // <script @nonce> : nonce CSP de la requête (voir ContentSecurityPolicy)
        Blade::directive('nonce', fn () => '<?php if ($__cspNonce = \\Illuminate\\Support\\Facades\\Vite::cspNonce()) { echo \'nonce="\' . e($__cspNonce) . \'"\'; } ?>');

        // Documents Word : échappe le texte écrit par PhpWord (sinon un "&" dans les
        // données produit un document.xml invalide que Word / LibreOffice refusent d'ouvrir)
        PhpWordSettings::setOutputEscapingEnabled(true);

        if (
            (App::environment('production') && (Config::get('app.force_https') === null))
            ||
            Config::get('app.force_https')
        ) {
            URL::forceScheme('https');
        }

        if (config('app.debug')) {
            DB::listen(function ($query) {
                Log::info(
                    $query->sql,
                    $query->bindings
                );
            });
        }

        // Si le logging LDAP est activé, on force l’utilisation du channel "ldap"
        if (config('ldap.logging.enabled')) {
            Container::setLogger(
                Log::channel('ldap')
            );
        }

        RateLimiter::for('api', function (Request $request) {
            if ($request->user()?->isAdmin()) {
                return Limit::none();
            }

            $limit = (int) config('api.rate_limit', 60);
            $decay = (int) config('api.rate_limit_decay', 1);

            return Limit::perMinutes($decay, $limit)
                ->by($request->user()?->id ?: $request->ip());
        });

        if (in_array('keycloak', Config::get('services.socialite_controller.providers'))) {
            Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
                $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
            });
        }

        if (in_array('oidc', Config::get('services.socialite_controller.providers'))) {
            $this->bootOIDCSocialite();
        }

        // Show appVersion from version.txt in Blades
        view()->composer('*', function ($view) {
            $version = trim(file_get_contents(base_path('version.txt')));
            $view->with('appVersion', $version);
        });
    }

    /**
     * Register Generic OpenID Connect Provider.
     */
    private function bootOIDCSocialite()
    {
        $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
        $socialite->extend(
            'oidc',
            function ($app) use ($socialite) {
                $config = $app['config']['services.oidc'];
                return $socialite->buildProvider(\App\Providers\Socialite\GenericSocialiteProvider::class, $config);
            }
        );
    }
}
