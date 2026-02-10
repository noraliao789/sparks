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
        Schema::create('event_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id');
            $table->foreignId('user_id');
            $table->text('text');
            $table->unsignedInteger('created_at')->default(0)->comment('訊息建立時間戳');
            $table->unsignedInteger('deleted_at')->nullable()->comment('訊息刪除時間戳');
            $table->index(['event_id', 'id']);          // 拉歷史訊息
            $table->index(['event_id', 'created_at']);  // 排序
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_messages');
    }
};
