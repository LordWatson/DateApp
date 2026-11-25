<?php

return [

    /*
    | Read by Illuminate\Http\Middleware\TrustProxies. Behind a load balancer the
    | app must trust it, or every visitor shares the proxy's IP address (rate
    | limits) and generated links use http. A comma-separated list of addresses
    | or ranges, or "*" to trust whichever server calls the app directly.
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
