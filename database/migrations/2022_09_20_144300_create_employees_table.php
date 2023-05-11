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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('sap_id', 100)->unique();
            $table->string('employee_code', 100)->unique();
            $table->string('employee_name', 250);
            $table->integer('position_id');
            $table->string('employee_level', 100);
            $table->string('email', 100)->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('direct_boss_id', 100)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->integer('member_unit_id')->nullable();
            $table->string('region', 100)->nullable();
            $table->string('unit', 100)->nullable();
            $table->tinyInteger('valid')->default(1);
            $table->date('start_work_date');
            $table->date('end_work_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
