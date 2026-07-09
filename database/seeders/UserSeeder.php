<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run(): void
    {
// 1. إنشاء الأدوار الأربعة الخاصة بالنظام في قاعدة البيانات (إذا لم تكن موجودة)
        $adminRole        = Role::firstOrCreate(['name' => 'Admin']);
        $managerRole      = Role::firstOrCreate(['name' => 'Warehouse Manager']);
        $storekeeperRole  = Role::firstOrCreate(['name' => 'Storekeeper']);
        $accountantRole   = Role::firstOrCreate(['name' => 'Accountant']);

        // 2. مصفوفة بيانات الأدمنز (جاد وعمر) متوافقة مع الـ Migration الخاص بك
        $users = [
            [
                'first_name' => 'jad',
                'last_name'  => 'Ali',
                'email'      => 'jadAli@gmail.com',
                'password'   => Hash::make('12345678'),
                'is_active'  => true,
            ],
            [
                'first_name' => 'omar',
                'last_name'  => 'Ali',
                'email'      => 'omarAli@gmail.com',
                'password'   => Hash::make('12345678'),
                'is_active'  => true,
            ],
        ];

        // 3. الدوران على المستخدمين وإنشاؤهم وإعطاؤهم دور الـ Admin
        foreach ($users as $userData) {
            $user = User::create($userData);

            // ربط المستخدم بدور الـ Admin
            $user->assignRole($adminRole);
        }
    }
}
