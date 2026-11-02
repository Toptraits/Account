<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('users', function(Blueprint $table){$table->string('mobile',10)->nullable()->unique();$table->string('role',30)->default('accountant');$table->boolean('is_active')->default(true);}); } public function down(): void { Schema::table('users', function(Blueprint $table){$table->dropUnique(['mobile']);$table->dropColumn(['mobile','role','is_active']);}); } };
