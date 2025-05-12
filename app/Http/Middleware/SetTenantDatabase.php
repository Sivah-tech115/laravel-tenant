<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\Country;
use PDO;
use Illuminate\Support\Facades\Artisan;

class SetTenantDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost(); // e.g., fr.compraspesaa.com

        // If main domain, skip tenant logic
        if ($host === 'localhost') {
            return $next($request);
        }
        $tenantIdentifier = explode('.', $host)[0]; // fr

        // Look up the tenant (country) based on the URL segment
        $country = Country::where('url', $tenantIdentifier)->first();

        // dd($country);

        if (!$country) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'The tenant identifier was not found in the system.'
            ], 404);
        }

        // Define full tenant connection config
        $tenantConnection = [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $country->db_name,
            'username' => 'root', // From the Country model
            'password' => 'Admin@1234', // From the Country model
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ];

        // Set the config
        Config::set('database.connections.tenant', $tenantConnection);
        // Config::set('database.default', 'tenant');

        // dd(config('database.connections.tenant'));

        // // First, run migration if not done yet
        try {
            // Before attempting to connect, run the migrations

            Artisan::call('migrate', [
                '--database' => 'tenant', // Use tenant connection
                '--force' => true, // Force migration
            ]);

            // Mark tenant as migrated
            $country->migrated = true;
            $country->save();


            // Now establish a connection to the tenant database
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Test connection to verify everything is set up
            DB::connection('tenant')->getPdo();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Tenant DB connection failed',
                'message' => $e->getMessage()
            ], 500);
        }

        // Proceed to the next middleware or controller
        return $next($request);
    }
}
