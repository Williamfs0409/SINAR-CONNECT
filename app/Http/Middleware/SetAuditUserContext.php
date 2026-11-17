<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetAuditUserContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            return $next($request);
        }

        $userId = $request->user()?->getAuthIdentifier();
        $connection->statement('SET @app_user_id = '.($userId === null ? 'NULL' : (string) (int) $userId));

        try {
            return $next($request);
        } finally {
            $connection->statement('SET @app_user_id = NULL');
        }
    }
}
