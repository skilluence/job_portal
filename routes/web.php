<?php

use App\Http\Controllers\Admin\AuditLogsController;
use App\Http\Controllers\Admin\CandidatesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\StudentAuthController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\StudentInfoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showLogin'])->name('login');
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login.get');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active.user', 'role:admin,recruiter,manager'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/candidates', [CandidatesController::class, 'index'])->name('candidates');
    Route::post('/candidates', [CandidatesController::class, 'store'])->name('candidates.store');
    Route::put('/candidates/{candidate}', [CandidatesController::class, 'update'])->name('candidates.update');
    Route::delete('/candidates/{candidate}', [CandidatesController::class, 'destroy'])->name('candidates.destroy');
    Route::post('/candidates/{candidate}/reveal-password', [CandidatesController::class, 'revealPassword'])
        ->name('candidates.reveal-password');
    Route::get('/candidates/{candidate}/files/{file}', [CandidatesController::class, 'downloadFile'])
        ->whereIn('file', ['cv', 'details'])
        ->name('candidates.files');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UsersController::class, 'index'])->name('users');
        Route::post('/users', [UsersController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');

        Route::get('/audit-logs', [AuditLogsController::class, 'index'])->name('audit-logs');

        Route::get('/import-export', [ImportExportController::class, 'index'])->name('import-export');
        Route::get('/import-export/export-candidates', [ImportExportController::class, 'exportCandidates'])->name('import-export.export-candidates');
        Route::get('/import-export/export-recruiters', [ImportExportController::class, 'exportRecruiters'])->name('import-export.export-recruiters');
        Route::get('/import-export/candidate-template', [ImportExportController::class, 'downloadCandidateTemplate'])->name('import-export.candidate-template');
        Route::get('/import-export/recruiter-template', [ImportExportController::class, 'downloadRecruiterTemplate'])->name('import-export.recruiter-template');
        Route::post('/import-export/import-candidates', [ImportExportController::class, 'importCandidates'])->name('import-export.import-candidates');
        Route::post('/import-export/import-recruiters', [ImportExportController::class, 'importRecruiters'])->name('import-export.import-recruiters');
    });
});

Route::get('/student', [StudentAuthController::class, 'showLogin'])->name('student.login');
Route::post('/student/login', [StudentAuthController::class, 'login'])->name('student.login.post');
Route::post('/student/logout', [StudentAuthController::class, 'logout'])->name('student.logout');

Route::prefix('student')->name('student.')->middleware('student.auth')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/info', [StudentInfoController::class, 'index'])->name('info');
    Route::post('/info', [StudentInfoController::class, 'update'])->name('info.update');
    Route::post('/info/documents', [StudentInfoController::class, 'updateDocuments'])->name('info.documents');
    Route::post('/info/password', [StudentInfoController::class, 'updatePassword'])->name('info.password');
    Route::get('/files/{file}', [StudentInfoController::class, 'downloadFile'])
        ->whereIn('file', ['cv', 'details'])
        ->name('files');
});
