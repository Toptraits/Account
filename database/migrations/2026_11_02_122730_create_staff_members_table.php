<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();

            // Optional link to an existing login account.
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('employee_number', 40)->unique();
            $table->string('name', 150);
            $table->string('designation', 100);
            $table->string('department', 100)->nullable();

            $table->string('email')->nullable();
            $table->string('mobile', 15)->nullable();

            $table->date('date_of_joining')->nullable();
            $table->string('employment_type', 40)->nullable();

            $table->string('status', 30)->default('active');

            $table->jsonb('subjects')->nullable();
            $table->jsonb('classes')->nullable();

            $table->text('address')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'designation']);
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
