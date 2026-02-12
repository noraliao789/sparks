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
        Schema::create('event_applies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('user_id');
            $table->string('message', 200)->nullable();
            $table->string('reason', 200)->nullable()->comment('拒絕理由');
            $table->unsignedTinyInteger('unlock_photo')->default(0)->comment('0: 不解鎖, 1: 解鎖');
            $table->unsignedTinyInteger('status')->default('0')->comment('0: 申請中, 1: 已通過, 2: 已拒絕, 3: 已取消');
            $table->unsignedInteger('created_at')->nullable();
            $table->unsignedInteger('updated_at')->nullable();
            $table->index(['event_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->unique(['event_id', 'user_id'], 'event_applies_event_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_applies');
    }
};
