<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ApprovedUser;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // 1. Check if user already exists by Google ID
            $existingUser = User::where('google_id', $googleUser->id)->first();
            
            if ($existingUser) {
                Auth::login($existingUser);
                return redirect()->intended('/admin');
            }

            // 2. Check if user exists by email but not linked to Google
            $userByEmail = User::where('email', $googleUser->email)->first();
            
            if ($userByEmail) {
                $userByEmail->update(['google_id' => $googleUser->id]);
                Auth::login($userByEmail);
                return redirect()->intended('/admin');
            }

            // 3. Check Whitelist for existing status
            $approvedUser = ApprovedUser::where('email', $googleUser->email)->first();

            if (!$approvedUser) {
                // Self-registration via Google - add to whitelist as pending
                ApprovedUser::create([
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'status' => 'pending',
                ]);

                return redirect()->route('filament.admin.auth.login')
                    ->withErrors(['email' => 'Your Google account has been added to the approval list. Please wait for an administrator to approve your access.']);
            }

            if ($approvedUser->status === 'pending') {
                return redirect()->route('filament.admin.auth.login')
                    ->withErrors(['email' => 'Your account is still pending approval. Please contact your Division Admin.']);
            }

            if ($approvedUser->status === 'rejected') {
                return redirect()->route('filament.admin.auth.login')
                    ->withErrors(['email' => 'Your request for access has been declined.']);
            }

            // 4. Create new user from Google Data (Status is Approved)
            $newUser = User::create([
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'password' => Hash::make(Str::random(24)),
                'approval_status' => 'approved',
            ]);

            // Link to existing Employee record (if one matches by email) so
            // delegated $user->name / $user->school_id resolve immediately.
            Employee::query()
                ->whereNull('user_id')
                ->where('email', $googleUser->email)
                ->update(['user_id' => $newUser->id]);

            if ($approvedUser->role) {
                $newUser->assignRole($approvedUser->role);
            }

            Auth::login($newUser);

            return redirect()->intended('/admin');

        } catch (Exception $e) {
            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['email' => 'Google Login failed. Please try again.']);
        }
    }
}
