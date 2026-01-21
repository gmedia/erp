<?php

namespace App\Actions\Users;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncUserForEmployeeAction
{
    /**
     * Create or update a user for a given employee.
     *
     * @param  \App\Models\Employee  $employee
     * @param  array  $data
     * @return \App\Models\User
     */
    public function execute(Employee $employee, array $data): User
    {
        return DB::transaction(function () use ($employee, $data) {
            $existingUser = $employee->user;
            
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            // Only update password if it's provided (not null/empty)
            if (!empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            if ($existingUser) {
                $existingUser->update($userData);
                $user = $existingUser;
            } else {
                // Ensure password is present for creation if not validated elsewhere, 
                // though FormRequest should handle 'required' context.
                // If password is somehow missing here for creation, DB might throw error depending on config,
                // but we trust upstream validation.
                $user = User::create($userData);

                $employee->update(['user_id' => $user->id]);
            }

            return $user;
        });
    }
}
