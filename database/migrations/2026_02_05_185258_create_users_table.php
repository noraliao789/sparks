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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('使用者顯示名稱');
            $table->string('email')->nullable()->unique()->comment('使用者 Email，可能為空');
            $table->timestamp('email_verified_at')->nullable()->comment('Email 驗證時間');
            $table->timestamp('line_verified_at')->nullable()->comment('Line 驗證時間');
            $table->string('password')->nullable()->comment('密碼（社群登入可能為空）');
            $table->string('avatar')->nullable()->comment('使用者頭像網址');
            $table->timestamp('last_login_at')->nullable()->comment('最後一次登入時間');
            $table->ipAddress('last_login_ip')->nullable()->comment('最後一次登入 IP 地址');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
