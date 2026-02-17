<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ApplicantController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\WorkflowController;
use App\Http\Controllers\Api\V1\WorkflowStepController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\Public\ApplicationController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\UserController;

/*
|--------------------------------------------------------------------------
| Public API Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/public')->group(function () {
    Route::post('/applications', [ApplicationController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

/*
|--------------------------------------------------------------------------
| Protected API Routes (Require Authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Users Management
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    // Positions
    Route::apiResource('positions', PositionController::class);

    // Applicants
    Route::apiResource('applicants', ApplicantController::class);
    Route::patch('applicants/{applicant}/move-step', [ApplicantController::class, 'moveStep']);
    Route::patch('applicants/{applicant}/status', [ApplicantController::class, 'updateStatus']);
    Route::post('applicants/{applicant}/notes', [ApplicantController::class, 'addNote']);
    Route::get('applicants/{applicant}/activities', [ApplicantController::class, 'activities']);

    // Clients
    Route::apiResource('clients', ClientController::class);

    // Branches
    Route::apiResource('branches', BranchController::class);
    Route::get('clients/{client}/branches', [BranchController::class, 'byClient']);

    // Workflows
    Route::apiResource('workflows', WorkflowController::class);
    Route::get('branches/{branch}/workflows', [WorkflowController::class, 'byBranch']);
    Route::post('workflows/{workflow}/steps/reorder', [WorkflowController::class, 'reorderSteps']);

    // Workflow Steps
    Route::apiResource('workflow-steps', WorkflowStepController::class)->except(['index']);
    Route::get('workflows/{workflow}/steps', [WorkflowStepController::class, 'byWorkflow']);

    // Reports
    Route::get('reports/applicants-by-source', [ReportController::class, 'applicantsBySource']);
    Route::get('reports/applicants-by-status', [ReportController::class, 'applicantsByStatus']);
    Route::get('reports/applicants-by-branch', [ReportController::class, 'applicantsByBranch']);
    Route::get('reports/conversion-rate', [ReportController::class, 'conversionRate']);
    Route::get('reports/export', [ReportController::class, 'export']);
});