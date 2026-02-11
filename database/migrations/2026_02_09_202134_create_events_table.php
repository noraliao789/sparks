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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('theme_id')->comment('活動主題');
            $table->unsignedInteger('pay_id')->comment('付款方式');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('num')->default(1)->comment('活動人數');
            $table->unsignedInteger('start_time')->nullable()->comment('活動開始時間');
            $table->unsignedInteger('end_time')->nullable()->comment('活動結束時間');
            $table->unsignedInteger('creator_by')->comment('活動建立者');
            $table->unsignedInteger('created_at')->nullable()->comment('活動建立時間戳');
            $table->unsignedInteger('updated_at')->nullable()->comment('活動更新時間戳');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
