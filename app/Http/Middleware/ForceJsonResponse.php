<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    /**
     * Force all API responses to be JSON by setting Accept header.
     */
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return $response;
        }

        // Ensure consistent JSON structure even for unexpected responses
        if ($response instanceof \Illuminate\Http\Response) {
            $content = $response->getContent();
            $data = json_decode($content, true);

            return response()->json(
                $data ?: ['success' => true, 'message' => $content, 'data' => null],
                $response->getStatusCode(),
                $response->headers->all()
            );
        }

        return $response;
    }
}
