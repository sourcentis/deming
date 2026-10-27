<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        \App\Models\RiskScoringConfig::clearCache();
        \App\Models\Configuration::clearCache();
    })
    ->in('Feature');

// Disable rate limiting for all feature tests
uses()->beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
})->in('Feature/API', 'Feature/Web');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
