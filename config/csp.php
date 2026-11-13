<?php

/*
|--------------------------------------------------------------------------
| Content Security Policy
|--------------------------------------------------------------------------
|
| En-tête Content-Security-Policy envoyé sur toutes les pages web.
| Les scripts inline sont autorisés par un nonce généré à chaque requête
| (voir la directive Blade @nonce) ; les handlers inline (onclick=...) sont
| interdits.
|
| CSP_REPORT_ONLY=true envoie l'en-tête Content-Security-Policy-Report-Only :
| le navigateur signale les violations dans la console sans rien bloquer.
|
*/

return [

    'enabled' => env('CSP_ENABLED', true),

    'report_only' => env('CSP_REPORT_ONLY', true),

    // URL qui reçoit les rapports de violation (directive report-uri)
    'report_uri' => env('CSP_REPORT_URI'),

    /*
    | Sources ajoutées à chaque directive, séparées par des espaces.
    | Exemple : CSP_IMG_SRC="https://images.example.com"
    */
    'extra' => [
        'default-src' => env('CSP_DEFAULT_SRC'),
        'script-src' => env('CSP_SCRIPT_SRC'),
        'style-src' => env('CSP_STYLE_SRC'),
        'img-src' => env('CSP_IMG_SRC'),
        'font-src' => env('CSP_FONT_SRC'),
        'connect-src' => env('CSP_CONNECT_SRC'),
        'form-action' => env('CSP_FORM_ACTION'),
        'frame-ancestors' => env('CSP_FRAME_ANCESTORS'),
    ],

];
