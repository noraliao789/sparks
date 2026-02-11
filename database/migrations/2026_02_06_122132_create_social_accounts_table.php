<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider')->comment('社群平台名稱，例如 google、line');
            $table->string('provider_user_id')->comment('社群平台的使用者唯一 ID');
            $table->string('email')->nullable()->comment('社群帳號 Email');
            $table->string('name')->nullable()->comment('社群帳號顯示名稱');
            $table->string('nickname')->nullable()->comment('社群帳號暱稱');
            $table->string('avatar')->nullable()->comment('社群帳號頭像');
            $table->json('raw')->nullable()->comment('社群平台返回的原始使用者資料');
            $table->unique(['provider', 'provider_user_id'], 'provider_user_unique');
            $table->index(['provider', 'email']);
            $table->unsignedInteger('created_at')->nullable()->comment('活動建立時間戳');
            $table->unsignedInteger('updated_at')->nullable()->comment('活動更新時間戳');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
