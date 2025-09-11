<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // distinguish between class row or entry log row
            $table->enum('type', ['class', 'entry'])->index();

            // shared fields
            $table->string('grade_level')->nullable();
            $table->string('section')->nullable();

            // ---- CLASS FIELDS ----
            $table->string('teacher')->nullable();
            $table->string('school_year')->nullable();
            $table->enum('status', ['active', 'inactive'])->nullable();

            // ---- ENTRY LOG FIELDS ----
            $table->date('log_date')->nullable();
            $table->string('student_name')->nullable();
            $table->string('student_id')->nullable();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
