<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $db = 'ok';
        } catch (\Throwable) {
            $db = 'error';
        }

        // Load balancers must drain unhealthy instances: report 503 on failure.
        return Response::json([
            'status' => $db === 'ok' ? 'ok' : 'degraded',
            'db' => $db,
            'time' => now()->toIso8601ZuluString(),
        ], $db === 'ok' ? 200 : 503);
    }
}
