<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'section')) {
                $table->string('section', 20)->nullable();
            }

            if (!Schema::hasColumn('students', 'mother_name')) {
                $table->string('mother_name')->nullable();
            }

            if (!Schema::hasColumn('students', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }

            if (!Schema::hasColumn('students', 'religion')) {
                $table->string('religion')->nullable();
            }

            if (!Schema::hasColumn('students', 'caste')) {
                $table->string('caste')->nullable();
            }

            if (!Schema::hasColumn('students', 'aadhaar_number')) {
                $table->string('aadhaar_number', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $columns = [
                'section',
                'mother_name',
                'date_of_birth',
                'religion',
                'caste',
                'aadhaar_number',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
