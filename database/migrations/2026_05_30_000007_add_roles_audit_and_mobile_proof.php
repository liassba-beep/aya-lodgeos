<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('manager')->after('property_id');
            $table->json('permissions')->nullable()->after('role');
        });

        DB::table('users')->whereNull('role')->orWhere('role', '')->update(['role' => 'manager']);

        $firstUserId = DB::table('users')->orderBy('id')->value('id');

        if ($firstUserId) {
            DB::table('users')->where('id', $firstUserId)->update(['role' => 'super_admin']);
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::table('daily_checklists', function (Blueprint $table) {
            $table->string('evidence_photo_path')->nullable()->after('evidence_note');
            $table->decimal('evidence_latitude', 10, 7)->nullable()->after('evidence_photo_path');
            $table->decimal('evidence_longitude', 10, 7)->nullable()->after('evidence_latitude');
            $table->string('evidence_qr_code')->nullable()->after('evidence_longitude');
            $table->foreignId('completed_by_user_id')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_checklists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by_user_id');
            $table->dropColumn('evidence_qr_code');
            $table->dropColumn('evidence_longitude');
            $table->dropColumn('evidence_latitude');
            $table->dropColumn('evidence_photo_path');
        });

        Schema::dropIfExists('audit_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['permissions', 'role']);
        });
    }
};
