<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
class ApiEnvelope
{
    public function handle($request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        $response = $next($request);
        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);
            if (isset($payload['current_page'], $payload['data'])) {
                $meta = collect($payload)->except('data')->all();
                $response->setData(['data' => $payload['data'], 'meta' => $meta]);
            }
        }
        return $response;
    }
}
