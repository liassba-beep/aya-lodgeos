<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->boolean('mobile_access_enabled')->default(false)->after('role');
            $table->string('mobile_pin_hash')->nullable()->after('mobile_access_enabled');
            $table->timestamp('last_mobile_login_at')->nullable()->after('mobile_pin_hash');
            $table->string('locale')->default('pt_PT')->after('permissions');
            $table->string('theme_mode')->default('system')->after('locale');

            $table->index(['phone', 'mobile_access_enabled']);
        });

        Schema::table('operational_alerts', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('property_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('operational_alerts', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone', 'mobile_access_enabled']);
            $table->dropColumn([
                'phone',
                'mobile_access_enabled',
                'mobile_pin_hash',
                'last_mobile_login_at',
                'locale',
                'theme_mode',
            ]);
        });
    }
};
