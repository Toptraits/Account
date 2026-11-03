<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('otp_challenges', function (Blueprint $table) {
            $table->string('email')->nullable()->index();
        });
        DB::statement('ALTER TABLE otp_challenges ALTER COLUMN mobile DROP NOT NULL');
    }
    public function down(): void {
        Schema::table('otp_challenges', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn('email');
        });
        // Keep mobile nullable to avoid breaking existing email-only challenge rows.
    }
};
