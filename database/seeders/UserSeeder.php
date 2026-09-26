<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Illuminate\Support\Facades\Hash; use App\Models\{User,Role};
class UserSeeder extends Seeder {
    public function run(): void {
        $admin = User::firstOrCreate(['email'=>'admin@lindu.local'],['name'=>'Super Admin','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true,'locale'=>'id','timezone'=>'Asia/Jakarta']);
        $r = Role::where('slug','super-admin')->first(); if($r) $admin->roles()->syncWithoutDetaching([$r->id]);
        $mgr = User::firstOrCreate(['email'=>'manager@lindu.local'],['name'=>'Manager','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $mr = Role::where('slug','manager')->first(); if($mr) $mgr->roles()->syncWithoutDetaching([$mr->id]);
        for($i=1;$i<=5;$i++){ User::firstOrCreate(['email'=>"member{$i}@lindu.local"],['name'=>"Member {$i}",'password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]); }
    }
}
