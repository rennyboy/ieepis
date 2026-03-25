<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

/**
 * EnsureAccountIsApproved Middleware
 *
 * Blocks users whose approval_status is not 'approved' from
 * accessing any authenticated route. Apply this middleware to
 * the Filament auth group in AdminPanelProvider.
 */
class EnsureAccountIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user instanceof User && $user->approval_status !== 'approved') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login')
                ->withErrors([
                    'email' => 'Your account is pending approval by the Division Administrator.',
                ]);
        }

        // If approved BUT missing school profile, redirect to wizard (unless already on it or logging out)
        if ($user instanceof User && $user->approval_status === 'approved' && 
            $user->hasRole(['school-admin']) && !$user->school_id && 
            !$request->is('admin/setup-school-profile*') && 
            !$request->is('admin/logout')) {
            return redirect('/admin/setup-school-profile');
        }

        return $next($request);
    }
}
