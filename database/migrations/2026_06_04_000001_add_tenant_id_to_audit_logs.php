<?php

use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\RoomType;
use App\Models\Subscription;
use App\Models\TenantAccount;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('property_id')
                ->constrained('tenant_accounts')
                ->nullOnDelete();

            $table->index(['tenant_id', 'created_at']);
        });

        DB::statement('
            UPDATE audit_logs
            SET tenant_id = properties.tenant_account_id
            FROM properties
            WHERE audit_logs.property_id = properties.id
              AND audit_logs.tenant_id IS NULL
        ');

        DB::statement('
            UPDATE audit_logs
            SET tenant_id = auditable_id
            WHERE auditable_type = ?
              AND tenant_id IS NULL
        ', [TenantAccount::class]);

        DB::statement('
            UPDATE audit_logs
            SET tenant_id = properties.tenant_account_id
            FROM properties
            WHERE audit_logs.auditable_type = ?
              AND audit_logs.auditable_id = properties.id
              AND audit_logs.tenant_id IS NULL
        ', [Property::class]);

        DB::statement('
            UPDATE audit_logs
            SET tenant_id = property_photos.tenant_id
            FROM property_photos
            WHERE audit_logs.auditable_type = ?
              AND audit_logs.auditable_id = property_photos.id
              AND audit_logs.tenant_id IS NULL
        ', [PropertyPhoto::class]);

        DB::statement('
            UPDATE audit_logs
            SET tenant_id = room_types.tenant_id
            FROM room_types
            WHERE audit_logs.auditable_type = ?
              AND audit_logs.auditable_id = room_types.id
              AND audit_logs.tenant_id IS NULL
        ', [RoomType::class]);

        DB::statement('
            UPDATE audit_logs
            SET tenant_id = testimonials.tenant_id
            FROM testimonials
            WHERE audit_logs.auditable_type = ?
              AND audit_logs.auditable_id = testimonials.id
              AND audit_logs.tenant_id IS NULL
        ', [Testimonial::class]);

        DB::statement('
            UPDATE audit_logs
            SET tenant_id = subscriptions.tenant_account_id
            FROM subscriptions
            WHERE audit_logs.auditable_type = ?
              AND audit_logs.auditable_id = subscriptions.id
              AND audit_logs.tenant_id IS NULL
        ', [Subscription::class]);

        DB::statement('
            UPDATE audit_logs
            SET tenant_id = properties.tenant_account_id
            FROM users
            JOIN properties ON users.property_id = properties.id
            WHERE audit_logs.auditable_type = ?
              AND audit_logs.auditable_id = users.id
              AND audit_logs.tenant_id IS NULL
        ', [User::class]);
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'created_at']);
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
