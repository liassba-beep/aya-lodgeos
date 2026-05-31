<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_entries', function (Blueprint $table) {
            $table->foreignId('tenant_account_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('screenshot_path')->nullable()->after('description');
            $table->text('master_response')->nullable()->after('priority');
            $table->timestamp('resolved_at')->nullable()->after('master_response');
        });

        DB::table('properties')
            ->whereNotNull('tenant_account_id')
            ->select(['id', 'tenant_account_id'])
            ->orderBy('id')
            ->each(function ($property): void {
                DB::table('feedback_entries')
                    ->where('property_id', $property->id)
                    ->whereNull('tenant_account_id')
                    ->update(['tenant_account_id' => $property->tenant_account_id]);
            });
    }

    public function down(): void
    {
        Schema::table('feedback_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_account_id');
            $table->dropColumn(['screenshot_path', 'master_response', 'resolved_at']);
        });
    }
};
