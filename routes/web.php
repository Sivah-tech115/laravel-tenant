<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Auth Routes (shared by main + tenant)
|--------------------------------------------------------------------------
*/
Auth::routes();

/*
|--------------------------------------------------------------------------
| Main Domain Routes (example: compraspesaa.com)
|--------------------------------------------------------------------------
*/
Route::domain('localhost')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', function () {
        return response()->json(['message' => 'Main Admin Panel']);
    })->name('main.admin');
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/test', function () {
    return view('welcome');
});


/*
|--------------------------------------------------------------------------
| Tenant Subdomain Routes (example: fr.compraspesaa.com)
|--------------------------------------------------------------------------
*/
Route::domain('{tenant}.compraspesaa.com')->middleware(['tenant', 'auth', 'role:tenant_admin'])->group(function () {

    Route::get('/', function ($tenant) {
        try {
            $tenantConnection = DB::connection('tenant');
            $dbName = $tenantConnection->getDatabaseName();
            $tables = $tenantConnection->select('SHOW TABLES');
            $tableCount = count($tables);

            return response()->json([
                'db_connection' => config('database.default'),
                'database' => $dbName,
                'tables_found' => $tableCount,
                'tables' => $tables,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database connection failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    });

    Route::get('/admin', function () {
        return response()->json(['message' => 'Tenant Admin Panel']);
    })->name('tenant.admin');
});
