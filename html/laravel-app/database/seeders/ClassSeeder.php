<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\ClassModel;
use App\Models\Admin;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = date('Y');
        
        $classes = [
            // 1年生
            ['class_id' => '1TOKUSHIN', 'class_name' => '1特進', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher1tokushin@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '1SHINGAKU', 'class_name' => '1進学', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher1shingaku@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '1CHORI', 'class_name' => '1調理', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher1chori@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '1JOHO', 'class_name' => '1情会', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher1joho@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '1FUKUSHI', 'class_name' => '1福祉', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher1fukushi@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '1SOGO1', 'class_name' => '1総合１', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher1sogo1@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '1SOGO2', 'class_name' => '1総合２', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher1sogo2@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '1SOGO3', 'class_name' => '1総合３', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher1sogo3@seiei.ac.jp', 'year_id' => $currentYear],
            
            // 2年生
            ['class_id' => '2TOKUSHIN', 'class_name' => '2特進', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher2tokushin@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '2SHINGAKU', 'class_name' => '2進学', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher2shingaku@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '2CHORI', 'class_name' => '2調理', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher2chori@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '2JOHO', 'class_name' => '2情会', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher2joho@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '2FUKUSHI', 'class_name' => '2福祉', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher2fukushi@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '2SOGO1', 'class_name' => '2総合１', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher2sogo1@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '2SOGO2', 'class_name' => '2総合２', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher2sogo2@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '2SOGO3', 'class_name' => '2総合３', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher2sogo3@seiei.ac.jp', 'year_id' => $currentYear],
            
            // 3年生
            ['class_id' => '3TOKUSHIN', 'class_name' => '3特進', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher3tokushin@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '3SHINGAKU', 'class_name' => '3進学', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher3shingaku@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '3CHORI', 'class_name' => '3調理', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher3chori@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '3JOHO', 'class_name' => '3情会', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher3joho@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '3FUKUSHI', 'class_name' => '3福祉', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher3fukushi@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '3SOGO1', 'class_name' => '3総合１', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher3sogo1@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '3SOGO2', 'class_name' => '3総合２', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher3sogo2@seiei.ac.jp', 'year_id' => $currentYear],
            ['class_id' => '3SOGO3', 'class_name' => '3総合３', 'teacher_name' => '担任未設定', 'teacher_email' => 'teacher3sogo3@seiei.ac.jp', 'year_id' => $currentYear],
        ];

        foreach ($classes as $class) {
            ClassModel::create($class);
            
            // 各クラスの担任管理者アカウントを作成
            Admin::updateOrCreate(
                ['email' => $class['teacher_email']],
                [
                    'name' => $class['teacher_name'],
                    'email' => $class['teacher_email'],
                    'password' => Hash::make('seiei2026'),
                    'class_id' => $class['class_id'],
                    'is_super_admin' => false,
                ]
            );
        }
        
        $this->command->info('クラスと担任アカウントを ' . count($classes) . ' 件作成しました。');
    }
}
