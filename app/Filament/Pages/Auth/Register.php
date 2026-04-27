<?php

namespace App\Filament\Pages\Auth;

use App\Models\ApprovedUser;
use App\Models\Employee;
use App\Models\User;
use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): User
    {
        // Check if the email is whitelisted and approved
        $approvedUser = ApprovedUser::where('email', $data['email'])
            ->where('status', 'approved')
            ->first();

        if (!$approvedUser) {
            // Check if it's pending so we can give a better message
            $pendingUser = ApprovedUser::where('email', $data['email'])
                ->where('status', 'pending')
                ->first();

            if ($pendingUser) {
                throw ValidationException::withMessages([
                    'data.email' => 'Your email is in our registry but pending approval. Please wait for an administrator to approve your account.',
                ]);
            }

            throw ValidationException::withMessages([
                'data.email' => 'This email is not authorized to register. Please contact your Division Administrator.',
            ]);
        }

        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'approval_status' => 'approved',
        ]);

        // Link to existing Employee record (if one matches by email) so
        // delegated $user->name / $user->school_id resolve immediately.
        Employee::query()
            ->whereNull('user_id')
            ->where('email', $data['email'])
            ->update(['user_id' => $user->id]);

        if ($approvedUser->role) {
            $user->assignRole($approvedUser->role);
        }

        return $user;
    }
}
