<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
// テスト用の認証情報を持つユーザーを作成
\App\Models\User::factory()->withPassword('password')->create();
// 追加のユーザーを作成
\App\Models\User::factory(9)->create();
        
        // 他のシーダーがあれば、ここに追加
    }
}
