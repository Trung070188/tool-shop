<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('unit_id')->nullable()->comment('Cơ sở');
            $table->tinyInteger('year')->comment('1. 2021-2022, 2. 2022-2023, 3. 2023-2024, 4. 2024-2025');
            $table->tinyInteger('type')->comment('1. Kế hoạch cấp cơ sở, 2. Kế hoạch cấp hệ thống');
            $table->tinyInteger('status')->comment('1. Active, 2. Un active');
            $table->tinyInteger('status_appraisal')->nullable()->comment('1. Đã thẩm định, 2. Chưa gửi thẩm định, 3. Từ chối, 4. Đang chờ thẩm định');
            $table->tinyInteger('status_approval')->nullable()->comment('1. Đã phê duyệt, 2. Chưa phê duyệt, 3. Từ chối phê duyệt, 4. Đang chờ phê duyệt');
            $table->integer('appraisal_by')->nullable()->comment('Người thẩm định');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plans');
    }
};
