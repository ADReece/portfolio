<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPhotoLimit
{
    /**
     * Stop uploads once the user's plan limit is reached.
     *
     * Uploads arrive as background requests from the uploader, so the
     * response is JSON with a readable message and an upgrade link rather
     * than a redirect the uploader can't follow.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->canUploadPhotos()) {
            return $next($request);
        }

        $message = ! $user?->subscriptionPlan
            ? 'Choose a plan to start uploading photos.'
            : "You've used all {$user->subscriptionPlan->photo_limit} photos on your plan. Upgrade to upload more.";

        if ($request->expectsJson() || $request->ajax() || $request->isMethod('post')) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'upgrade_url' => route('pricing'),
            ], 403);
        }

        return redirect()->route('pricing')->with('error', $message);
    }
}
