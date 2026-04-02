<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $district = District::firstOrCreate(
            ['slug' => 'demo-division'],
            [
                'name' => 'Demo School Division',
                'sso_provider' => 'local',
            ]
        );

        $school = School::firstOrCreate(
            [
                'district_id' => $district->id,
                'name' => 'Riverside Elementary',
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'district_id' => $district->id,
                'school_id' => null,
                'name' => 'District Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('district_admin');

        $teacher = User::updateOrCreate(
            ['email' => 'teacher@demo.test'],
            [
                'district_id' => $district->id,
                'school_id' => $school->id,
                'name' => 'Ms. Taylor',
                'password' => Hash::make('password'),
            ]
        );
        $teacher->assignRole('teacher');

        $student = User::updateOrCreate(
            ['email' => 'student@demo.test'],
            [
                'district_id' => $district->id,
                'school_id' => $school->id,
                'name' => 'Alex Student',
                'password' => Hash::make('password'),
                'grade_level' => '5',
            ]
        );
        $student->assignRole('student');
    }
}
