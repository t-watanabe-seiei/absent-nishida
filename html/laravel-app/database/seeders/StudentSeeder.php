<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = [
            // 1年特進
            ['seito_id' => 1001, 'seito_name' => '山田太郎', 'seito_number' => 1, 'class_id' => '1TOKUSHIN', 'seito_initial_email' => '1001@seiei.ac.jp'],
            ['seito_id' => 1002, 'seito_name' => '田中花子', 'seito_number' => 2, 'class_id' => '1TOKUSHIN', 'seito_initial_email' => '1002@seiei.ac.jp'],
            ['seito_id' => 1003, 'seito_name' => '佐藤次郎', 'seito_number' => 3, 'class_id' => '1TOKUSHIN', 'seito_initial_email' => '1003@seiei.ac.jp'],
            
            // 1年進学
            ['seito_id' => 1004, 'seito_name' => '鈴木美咲', 'seito_number' => 1, 'class_id' => '1SHINGAKU', 'seito_initial_email' => '1004@seiei.ac.jp'],
            ['seito_id' => 1005, 'seito_name' => '高橋健太', 'seito_number' => 2, 'class_id' => '1SHINGAKU', 'seito_initial_email' => '1005@seiei.ac.jp'],
            
            // 1年調理
            ['seito_id' => 1006, 'seito_name' => '伊藤さくら', 'seito_number' => 1, 'class_id' => '1CHORI', 'seito_initial_email' => '1006@seiei.ac.jp'],
            ['seito_id' => 1007, 'seito_name' => '渡辺大輔', 'seito_number' => 2, 'class_id' => '1CHORI', 'seito_initial_email' => '1007@seiei.ac.jp'],
            
            // 1年情会
            ['seito_id' => 1008, 'seito_name' => '中村愛', 'seito_number' => 1, 'class_id' => '1JOHO', 'seito_initial_email' => '1008@seiei.ac.jp'],
            ['seito_id' => 1009, 'seito_name' => '小林翔太', 'seito_number' => 2, 'class_id' => '1JOHO', 'seito_initial_email' => '1009@seiei.ac.jp'],
            
            // 1年総合１
            ['seito_id' => 1010, 'seito_name' => '加藤結衣', 'seito_number' => 1, 'class_id' => '1SOGO1', 'seito_initial_email' => '1010@seiei.ac.jp'],
            
            // 2年特進
            ['seito_id' => 2001, 'seito_name' => '吉田陽介', 'seito_number' => 1, 'class_id' => '2TOKUSHIN', 'seito_initial_email' => '2001@seiei.ac.jp'],
            ['seito_id' => 2002, 'seito_name' => '山本優奈', 'seito_number' => 2, 'class_id' => '2TOKUSHIN', 'seito_initial_email' => '2002@seiei.ac.jp'],
            
            // 2年進学
            ['seito_id' => 2003, 'seito_name' => '岡田浩二', 'seito_number' => 1, 'class_id' => '2SHINGAKU', 'seito_initial_email' => '2003@seiei.ac.jp'],
            ['seito_id' => 2004, 'seito_name' => '松本美咲', 'seito_number' => 2, 'class_id' => '2SHINGAKU', 'seito_initial_email' => '2004@seiei.ac.jp'],
            
            // 2年総合２
            ['seito_id' => 2005, 'seito_name' => '井上太一', 'seito_number' => 1, 'class_id' => '2SOGO2', 'seito_initial_email' => '2005@seiei.ac.jp'],
            
            // 3年特進
            ['seito_id' => 3001, 'seito_name' => '木村里奈', 'seito_number' => 1, 'class_id' => '3TOKUSHIN', 'seito_initial_email' => '3001@seiei.ac.jp'],
            ['seito_id' => 3002, 'seito_name' => '林大樹', 'seito_number' => 2, 'class_id' => '3TOKUSHIN', 'seito_initial_email' => '3002@seiei.ac.jp'],
            
            // 3年総合３
            ['seito_id' => 3003, 'seito_name' => '斉藤葵', 'seito_number' => 1, 'class_id' => '3SOGO3', 'seito_initial_email' => '3003@seiei.ac.jp'],
        ];

        foreach ($students as $student) {
            Student::create($student);
        }
    }
}
