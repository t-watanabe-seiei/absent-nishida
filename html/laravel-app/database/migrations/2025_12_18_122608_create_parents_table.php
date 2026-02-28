<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->string('seito_id');
            $table->string('parent_name');
            $table->string('parent_relationship');
            $table->string('parent_tel')->nullable();
            $table->string('parent_initial_email')->unique(); // ログイン用メールアドレス（必須、ユニーク）
            $table->string('parent_initial_password'); // ログイン用パスワード（必須、bcrypt暗号化）
            $table->string('parent_email')->nullable()->unique(); // 2段階認証送信先（初回ログイン時に登録）
            $table->string('parent_password')->nullable(); // 将来の拡張用
            $table->timestamps();
            
            $table->foreign('seito_id')->references('seito_id')->on('students')->onDelete('cascade');
            $table->index('seito_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};
