<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Make sure the classes table exists from its migration
        if (!Schema::hasColumn('students', 'class_id')) {
            Schema::table('students', function (Blueprint $table) {
                // nullable so existing rows won’t break
                $table->foreignId('class_id')
                    ->nullable()
                    ->after('parent_contact')
                    ->constrained('classes')
                    ->nullOnDelete(); // if a class is deleted, set NULL on students
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'class_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropConstrainedForeignId('class_id');
            });
        }
    }
};
