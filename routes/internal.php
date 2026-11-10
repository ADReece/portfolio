<?php

use App\Http\Controllers\InternalCustomDomainsController;
use Illuminate\Support\Facades\Route;

// Server-to-server endpoints: no session, CSRF or rate limit.
Route::get('/internal/custom-domains', InternalCustomDomainsController::class)->name('internal.custom-domains');
