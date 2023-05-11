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
        Schema::create('plan_contents', function (Blueprint $table) {
            $table->id();
            $table->integer('plan_id');
            $table->integer('position_id')->comment('Chức danh');
            $table->tinyInteger('address_area')->nullable()->comment('1. Miền Bắc, 2. Miền Nam');
            $table->tinyInteger('level')->nullable()->comment('1. Mầm non, 2. Tiểu học, 3. Trung học');
            $table->tinyInteger('joining_time')->nullable()->comment('1. Mới, 2. Cũ, 3. Tất cả');
            $table->integer('standard_id')->comment('Tiêu chuẩn đào tạo');
            $table->integer('content_id')->comment('Nội dung đào tạo');
            $table->integer('course_id')->nullable()->comment('Khóa học');
            $table->tinyInteger('method')->nullable()->comment('1. Đào tạo online, 2. Đào tạo tập trung, 3. Huấn luyện, 4. Đào tạo OJT, 5. Hội thảo, 6. Sinh hoạt chuyên môn, 7. Khác');
            $table->text('target')->nullable()->comment('Mục tiêu đào tạo');
            $table->integer('hours')->nullable()->comment('Thời lượng đào tạo(h)');
            $table->string('time')->nullable()->comment('Thời gian đào tạo');
            $table->tinyInteger('lecturer')->nullable()->comment('1. Giảng viên, 2. Thuê ngoài');
            $table->integer('unit_id')->nullable()->comment('Đơn vị đào tạo');
            $table->integer('lecturer_id')->nullable()->comment('Giảng viên');
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
        Schema::dropIfExists('plan_contents');
    }
};
