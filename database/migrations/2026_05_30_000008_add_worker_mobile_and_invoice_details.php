<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('invoice_logo_path')->nullable()->after('status');
            $table->string('legal_name')->nullable()->after('invoice_logo_path');
            $table->string('nuit')->nullable()->after('legal_name');
            $table->string('invoice_phone')->nullable()->after('phone');
            $table->string('invoice_email')->nullable()->after('invoice_phone');
            $table->text('invoice_footer')->nullable()->after('notes');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->string('nuit')->nullable()->after('phone');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(16)->after('tax_amount');
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->boolean('mobile_access_enabled')->default(false)->after('status');
            $table->string('mobile_pin_hash')->nullable()->after('mobile_access_enabled');
            $table->string('checkin_photo_path')->nullable()->after('mobile_pin_hash');
            $table->timestamp('checked_in_at')->nullable()->after('checkin_photo_path');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
            $table->timestamp('last_mobile_login_at')->nullable()->after('checked_out_at');
        });

        Schema::table('operational_tasks', function (Blueprint $table) {
            $table->string('evidence_photo_path')->nullable()->after('completed_at');
            $table->string('evidence_qr_code')->nullable()->after('evidence_photo_path');
            $table->foreignId('completed_by_staff_member_id')->nullable()->after('evidence_qr_code')->constrained('staff_members')->nullOnDelete();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('mobile_checkin_photo_path')->nullable()->after('notes');
            $table->foreignId('mobile_checked_in_by')->nullable()->after('mobile_checkin_photo_path')->constrained('staff_members')->nullOnDelete();
            $table->foreignId('mobile_checked_out_by')->nullable()->after('mobile_checked_in_by')->constrained('staff_members')->nullOnDelete();
            $table->timestamp('mobile_checked_in_at')->nullable()->after('mobile_checked_out_by');
            $table->timestamp('mobile_checked_out_at')->nullable()->after('mobile_checked_in_at');
        });

        Schema::create('maintenance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('priority')->default('normal');
            $table->string('status')->default('reported');
            $table->string('photo_path')->nullable();
            $table->string('qr_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('utility_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $table->date('reading_date');
            $table->string('meter_name')->default('Credelec');
            $table->string('meter_number')->nullable();
            $table->decimal('balance_kwh', 12, 2)->nullable();
            $table->decimal('balance_amount', 12, 2)->nullable();
            $table->string('qr_code')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('product_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stock_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->string('status')->default('requested');
            $table->date('needed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_requisitions');
        Schema::dropIfExists('utility_readings');
        Schema::dropIfExists('maintenance_reports');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('mobile_checked_out_at');
            $table->dropColumn('mobile_checked_in_at');
            $table->dropConstrainedForeignId('mobile_checked_out_by');
            $table->dropConstrainedForeignId('mobile_checked_in_by');
            $table->dropColumn('mobile_checkin_photo_path');
        });

        Schema::table('operational_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by_staff_member_id');
            $table->dropColumn('evidence_qr_code');
            $table->dropColumn('evidence_photo_path');
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn([
                'last_mobile_login_at',
                'checked_out_at',
                'checked_in_at',
                'checkin_photo_path',
                'mobile_pin_hash',
                'mobile_access_enabled',
            ]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('nuit');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_footer',
                'invoice_email',
                'invoice_phone',
                'nuit',
                'legal_name',
                'invoice_logo_path',
            ]);
        });
    }
};
