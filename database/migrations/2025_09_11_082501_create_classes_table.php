<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /* -------------------------------
         * STUDENTS
         * ------------------------------- */
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 50)->unique()->index();
            $table->string('full_name', 191);
            $table->string('grade_level', 50);
            $table->string('section', 50)->nullable();
            $table->string('parent_contact', 20)->nullable();
            $table->timestamps();
        });

        /* -------------------------------
         * CLASSES
         * ------------------------------- */
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('grade_level', 50);
            $table->string('section', 50);
            $table->string('teacher', 191)->nullable();
            $table->string('school_year', 20)->default('2024-2025')->index();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();

            $table->unique(['grade_level', 'section', 'school_year'], 'classes_unique_idx');
        });

        /* -------------------------------
         * ATTENDANCES
         * unified for class & gate entry
         * ------------------------------- */
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // distinguish record type
            $table->enum('type', ['class', 'entry'])->index();

            $table->foreignId('class_id')->nullable()
                ->constrained('classes')->nullOnDelete();
            $table->foreignId('student_id')->nullable()
                ->constrained('students')->nullOnDelete();

            // common fields
            $table->date('log_date')->index();
            $table->enum('status', [
                'present',
                'late',
                'absent',
                'excused', // for class
                'in',
                'out'                           // for gate entries
            ])->nullable()->index();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->string('note', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type',  'student_id'], 'att_type_session_student_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('students');
    }
};
