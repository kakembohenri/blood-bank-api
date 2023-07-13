<?php

use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BloodProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestPreacherController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\HospitalStaffController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Models\GuestPreacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Users controller
Route::controller(AuthController::class)->group(function () {
    Route::post("/login", "login");

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post("/logout", "logout");

        // Verify hospital accounts
        Route::put("/verify-hospital/{id}", "VerifyHospital");

        // Reject hospital accounts
        Route::put("/reject-hospital/{id}", "RejectHospital");
    });
});

// Hospital controller
Route::controller(HospitalController::class)->group(function () {
    Route::post("/create-account", "createAccount");
});

// Dashboard controller
Route::controller(DashboardController::class)->group(function () {

    Route::group(['middleware' => ['auth:sanctum']], function () {
        // Blood bank dashboard
        Route::get("/bloodBank/dashboard", "BloodBankDashboard");
        // Hospital dashboard
        Route::get("/hospital/dashboard", "HospitalDashboard");
    });
});

// Orders controller
Route::controller(OrdersController::class)->group(function () {

    Route::group(['middleware' => ['auth:sanctum']], function () {
        // Route::get("/bulkOrders/{district?}/{date?}/{time?}/{itemsPerPage?}/{lastPage?}/{firstPage?}", "bulkOrders");
        // Get bulk orders
        Route::get("/bulkOrders", "bulkOrders");
        // Get hospital inventory
        Route::get("/hospitalInventory", "HospitalInventory");
        // Create bulk orders
        Route::post("/bulkOrders", "createBulkOrder");
        // Update bulk orders
        // Route::put("/bulkOrders", "UpdateBulkOrder");
        // Approve bulk order
        Route::put("/approve/bulkOrder/{id}", "ApproveBulkOrder");
        // Reject bulk order
        Route::post("/reject", "RejectBulkOrder");
        // Delete Bulk Order
        Route::delete("/bulkOrders", "DeleteBulkOrder");
    });
});

// Blood products
Route::controller(BloodProductController::class)->group(function () {
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get("/bloodComponents", "index");
        Route::get("/getBloodUnit/{id}", "getBloodUnit");
        Route::post("/createBloodUnit", "createBloodUnit");
        Route::put("/updateBloodUnit", "updateBloodUnit");
        Route::delete("/deleteBloodUnit/{id}", "deleteBloodUnit");
    });
});

// Hospital staff
Route::controller(HospitalStaffController::class)->group(function () {
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post("/createHospitalStaff", "createHospitalStaff");
        Route::put("/updateHospitalStaff", "updateHospitalStaff");
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
