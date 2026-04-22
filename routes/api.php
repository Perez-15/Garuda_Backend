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
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\CustomColumnController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\PerformanceController;
use App\Http\Controllers\Api\V1\LoaWebhookController;
use App\Http\Controllers\Api\V1\WebsiteApplicationController;
use App\Http\Controllers\Api\Public\ApplicationController;
use App\Http\Controllers\Api\Public\JobPostingController as PublicJobPostingController;
use App\Http\Controllers\Api\Marketing\AuthController as MarketingAuthController;
use App\Http\Controllers\Api\Marketing\JobPostingController as MarketingJobPostingController;
use App\Http\Controllers\Api\Marketing\ContactInquiryController as MarketingContactInquiryController;
use App\Http\Controllers\Api\V1\ClientProspectController;
/*
|--------------------------------------------------------------------------
| Public API Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/public')->group(function () {
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/jobs',          [PublicJobPostingController::class, 'index']);
    Route::post('/contact',      [MarketingContactInquiryController::class, 'store']); // ← new
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/v1/loa/webhook', [LoaWebhookController::class, 'receive']);

/*
|--------------------------------------------------------------------------
| Marketing Auth (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::prefix('marketing')->group(function () {
        Route::post('/login', [MarketingAuthController::class, 'login']);
    });
});

/*
|--------------------------------------------------------------------------
| Marketing Protected Routes (Sanctum — marketing/hr_admin/super_admin roles)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/marketing')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [MarketingAuthController::class, 'logout']);
    Route::get('/me',      [MarketingAuthController::class, 'me']);

    // ── Job Postings CRUD ──────────────────────────────────────────────────────
    Route::get   ('/jobs',                     [MarketingJobPostingController::class, 'index']);
    Route::post  ('/jobs',                     [MarketingJobPostingController::class, 'store']);
    Route::get   ('/jobs/{jobPosting}',        [MarketingJobPostingController::class, 'show']);
    Route::put   ('/jobs/{jobPosting}',        [MarketingJobPostingController::class, 'update']);
    Route::delete('/jobs/{jobPosting}',        [MarketingJobPostingController::class, 'destroy']);
    Route::patch ('/jobs/{jobPosting}/toggle', [MarketingJobPostingController::class, 'toggleActive']);

    // ── Contact Inquiries ──────────────────────────────────────────────────────
    Route::get   ('/contact-inquiries',                        [MarketingContactInquiryController::class, 'index']);
    Route::patch ('/contact-inquiries/{inquiry}/read',         [MarketingContactInquiryController::class, 'markRead']);
    Route::patch ('/contact-inquiries/{inquiry}/archive',      [MarketingContactInquiryController::class, 'archive']);
    Route::delete('/contact-inquiries/{inquiry}',              [MarketingContactInquiryController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Protected API Routes (Require Authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // ── Auth ───────────────────────────────────────────────────────────────────
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // ── Users (Internal Employees) ─────────────────────────────────────────────
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/status',        [UserController::class, 'updateStatus']);
    Route::patch('users/{user}/requirements',  [UserController::class, 'updateRequirements']);
    Route::patch('users/{user}/custom-fields', [UserController::class, 'updateCustomFields']);
    Route::post('users/{user}/branches',       [UserController::class, 'assignBranches']);
    Route::get('users/{user}/branches',        [UserController::class, 'branches']);
    Route::post('/users/{user}/photo',         [UserController::class, 'uploadPhoto']);
    Route::delete('/users/{user}/photo',       [UserController::class, 'deletePhoto']);

    // ── Attendance ─────────────────────────────────────────────────────────────
    Route::get('attendance/today',      [AttendanceController::class, 'today']);
    Route::get('attendance/team',       [AttendanceController::class, 'team']);
    Route::get('attendance',            [AttendanceController::class, 'index']);
    Route::post('attendance/time-in',   [AttendanceController::class, 'timeIn']);
    Route::patch('attendance/time-out', [AttendanceController::class, 'timeOut']);

    // ── Dashboard ──────────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ── Positions ──────────────────────────────────────────────────────────────
    Route::apiResource('positions', PositionController::class);

    // ── Applicants ─────────────────────────────────────────────────────────────
    // IMPORTANT: named routes (stats, trashed) must come BEFORE apiResource
    // so Laravel does not treat them as {applicant} wildcards.
    Route::get('applicants/stats',                        [ApplicantController::class, 'stats']);
    Route::get('applicants/trashed',                      [ApplicantController::class, 'trashed']);
    Route::patch('applicants/{id}/restore',               [ApplicantController::class, 'restore']);
    Route::delete('applicants/{id}/force-delete',         [ApplicantController::class, 'forceDelete']);
    Route::apiResource('applicants', ApplicantController::class);
    Route::patch('applicants/{applicant}/move-step',      [ApplicantController::class, 'moveStep']);
    Route::patch('applicants/{applicant}/status',         [ApplicantController::class, 'updateStatus']);
    Route::patch('applicants/{applicant}/custom-fields',  [ApplicantController::class, 'updateCustomFields']);
    Route::post('applicants/{applicant}/notes',           [ApplicantController::class, 'addNote']);
    Route::get('applicants/{applicant}/activities',       [ApplicantController::class, 'activities']);

    // ── Employees (External / Hired) ───────────────────────────────────────────
    // IMPORTANT: named routes (stats, trashed, convert) must come BEFORE apiResource
    // so Laravel does not treat them as {employee} wildcards.
    Route::get('employees/stats',                         [EmployeeController::class, 'stats']);
    Route::get('employees/trashed',                       [EmployeeController::class, 'trashed']);
    Route::patch('employees/{id}/restore',                [EmployeeController::class, 'restore']);
    Route::delete('employees/{id}/force-delete',          [EmployeeController::class, 'forceDelete']);
    Route::post('employees/convert/{applicant}',          [EmployeeController::class, 'convertFromApplicant']);
    Route::apiResource('employees', EmployeeController::class);
    Route::patch('employees/{employee}/status',           [EmployeeController::class, 'updateStatus']);
    Route::patch('employees/{employee}/custom-fields',    [EmployeeController::class, 'updateCustomFields']);

    // HR Actions (Memo / IR / LOA)
    Route::get('employees/{employee}/hr-actions',             [EmployeeController::class, 'hrActions']);
    Route::post('employees/{employee}/hr-actions',            [EmployeeController::class, 'addHrAction']);
    Route::patch('employees/{employee}/hr-actions/{action}',  [EmployeeController::class, 'updateHrAction']);
    Route::delete('employees/{employee}/hr-actions/{action}', [EmployeeController::class, 'deleteHrAction']);
    Route::get('/{employee}/hr-actions/{action}/file-url',    [EmployeeController::class, 'hrActionFileUrl']);

    // ── Clients ────────────────────────────────────────────────────────────────
    Route::apiResource('clients', ClientController::class);
     Route::apiResource('client-prospects', ClientProspectController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    // ── Branches ───────────────────────────────────────────────────────────────
    Route::apiResource('branches', BranchController::class);
    Route::get('clients/{client}/branches', [BranchController::class, 'byClient']);

    // ── Workflows ──────────────────────────────────────────────────────────────
    Route::apiResource('workflows', WorkflowController::class);
    Route::get('branches/{branch}/workflows',         [WorkflowController::class, 'byBranch']);
    Route::post('workflows/{workflow}/steps/reorder', [WorkflowController::class, 'reorderSteps']);

    // ── Workflow Steps ─────────────────────────────────────────────────────────
    Route::apiResource('workflow-steps', WorkflowStepController::class)->except(['index']);
    Route::get('workflows/{workflow}/steps', [WorkflowStepController::class, 'byWorkflow']);

    // ── Reports ────────────────────────────────────────────────────────────────
    Route::get('reports/applicants-by-source', [ReportController::class, 'applicantsBySource']);
    Route::get('reports/applicants-by-status', [ReportController::class, 'applicantsByStatus']);
    Route::get('reports/applicants-by-branch', [ReportController::class, 'applicantsByBranch']);
    Route::get('reports/conversion-rate',      [ReportController::class, 'conversionRate']);
    Route::get('reports/export',               [ReportController::class, 'export']);
    Route::get('reports/top-recruiters',       [ReportController::class, 'topRecruiters']);
    Route::get('/reports/applicants-trend', [ReportController::class, 'applicantsTrend']);
    // ── Performance ────────────────────────────────────────────────────────────
    Route::get('/performance/ta',                       [PerformanceController::class, 'taPerformance']);
    Route::get('/performance/branches',                 [PerformanceController::class, 'branchPerformance']);
    Route::get('/performance/ta/{taId}/applicants',     [PerformanceController::class, 'taApplicants']);

    // ── Custom Columns ─────────────────────────────────────────────────────────
    // IMPORTANT: named routes must come BEFORE apiResource
    Route::get   ('custom-columns/tables',        [CustomColumnController::class, 'getTables']);
    Route::post  ('custom-columns/tables',        [CustomColumnController::class, 'storeTable']);
    Route::patch ('custom-columns/tables/{page}', [CustomColumnController::class, 'updateTable']);
    Route::delete('custom-columns/tables/{page}', [CustomColumnController::class, 'destroyTable']);
    Route::post  ('custom-columns/reorder',       [CustomColumnController::class, 'reorder']);
    Route::apiResource('custom-columns', CustomColumnController::class);
    Route::post('/custom-columns/batch', [CustomColumnController::class, 'batch']); 
    // ── Website Applications (from marketing site) ─────────────────────────────
    // IMPORTANT: pending-count must come BEFORE {websiteApplication} wildcard
    Route::get ('website-applications/pending-count',               [WebsiteApplicationController::class, 'pendingCount']);
    Route::get ('website-applications',                             [WebsiteApplicationController::class, 'index']);
    Route::post('website-applications/{websiteApplication}/accept', [WebsiteApplicationController::class, 'accept']);
    Route::post('website-applications/{websiteApplication}/dismiss',[WebsiteApplicationController::class, 'dismiss']);
    Route::get ('website-applications/{websiteApplication}/resume', [WebsiteApplicationController::class, 'resumeUrl']);
});