<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\superAdminEmail;

class StaffService
{
  public function createStaff(array $data, string $role)
    {
        return DB::transaction(function () use ($data, $role) {

            $user = User::create([
                'first_name'       => $data['first_name'],
                'last_name'        => $data['last_name'],
                'email'            => $data['email'],
                'phone'            => $data['phone'] ?? null,
                'password'         => Hash::make($data['password']),
                'is_active'        => true,
                'warehouse_id'     => $data['warehouse_id'] ?? null,
                'shift_type'       => $data['shift_type'] ?? null,
                'salary'           => $data['salary'] ?? null,
                'management_level' => $data['management_level'] ?? null,
                'hiring_date'      => $data['hiring_date'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);
            $user->assignRole($role);
            Mail::to($data['email'])->send(new SuperAdminEmail($data['email'], $data['password']));
            return $user;
        });
    }
}
