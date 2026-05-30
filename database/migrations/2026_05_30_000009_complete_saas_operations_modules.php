<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->unsignedInteger('property_limit')->nullable();
            $table->unsignedInteger('user_limit')->nullable();
            $table->json('features')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('saas_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('trial');
            $table->date('starts_at')->nullable();
            $table->date('renews_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->decimal('monthly_amount', 12, 2)->default(0);
            $table->string('billing_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('tenant_account_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->text('cancellation_policy')->nullable()->after('invoice_footer');
            $table->decimal('deposit_percent', 5, 2)->default(50)->after('cancellation_policy');
            $table->text('house_rules')->nullable()->after('deposit_percent');
            $table->unsignedSmallInteger('cleaning_interval_days')->default(3)->after('house_rules');
            $table->json('room_inventory_template')->nullable()->after('cleaning_interval_days');
            $table->json('meals_and_services')->nullable()->after('room_inventory_template');
        });

        Schema::create('property_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('manager');
            $table->json('permissions')->nullable();
            $table->timestamps();
            $table->unique(['property_id', 'user_id']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(16);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->date('issued_at');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('method')->default('cash');
            $table->string('status')->default('issued');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $table->date('closure_date');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('cash_received', 12, 2)->default(0);
            $table->decimal('card_received', 12, 2)->default(0);
            $table->decimal('expenses_paid', 12, 2)->default(0);
            $table->decimal('expected_balance', 12, 2)->default(0);
            $table->decimal('counted_balance', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('remote_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('subject');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('operational_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('severity')->default('info');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('status')->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('room_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('expected_quantity', 12, 2)->default(1);
            $table->decimal('current_quantity', 12, 2)->default(1);
            $table->decimal('replacement_cost', 12, 2)->default(0);
            $table->string('status')->default('ok');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('damage_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $table->date('count_date');
            $table->decimal('system_quantity', 12, 2)->default(0);
            $table->decimal('counted_quantity', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_member_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('checkin_photo_path')->nullable();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_member_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('vacation');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default('requested');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('owner_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->date('report_date');
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('expenses', 12, 2)->default(0);
            $table->unsignedInteger('arrivals')->default(0);
            $table->unsignedInteger('departures')->default(0);
            $table->unsignedInteger('occupied_rooms')->default(0);
            $table->unsignedInteger('open_tasks')->default(0);
            $table->unsignedInteger('open_alerts')->default(0);
            $table->string('status')->default('draft');
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->unique(['property_id', 'report_date']);
        });

        Schema::create('knowledge_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->default('operacao');
            $table->string('title');
            $table->longText('content');
            $table->string('status')->default('published');
            $table->timestamps();
        });

        Schema::create('feedback_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('opinion');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->default('normal');
            $table->timestamps();
        });

        DB::table('saas_plans')->insert([
            ['name' => 'Freemium', 'code' => 'freemium', 'monthly_price' => 0, 'property_limit' => 1, 'user_limit' => 2, 'features' => json_encode(['Reservas basicas', 'Dashboard simples']), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Starter', 'code' => 'starter', 'monthly_price' => 1499, 'property_limit' => 1, 'user_limit' => 5, 'features' => json_encode(['Reservas', 'Faturacao', 'Stock basico']), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pro', 'code' => 'pro', 'monthly_price' => 3499, 'property_limit' => 3, 'user_limit' => 15, 'features' => json_encode(['Multi-propriedade', 'Mobile staff', 'Auditoria', 'Relatorios']), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Premium', 'code' => 'premium', 'monthly_price' => 6999, 'property_limit' => null, 'user_limit' => null, 'features' => json_encode(['Tudo do Pro', 'Suporte prioritario', 'Automacoes avancadas']), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_entries');
        Schema::dropIfExists('knowledge_guides');
        Schema::dropIfExists('owner_daily_reports');
        Schema::dropIfExists('staff_leaves');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('damage_charges');
        Schema::dropIfExists('room_inventories');
        Schema::dropIfExists('operational_alerts');
        Schema::dropIfExists('remote_approvals');
        Schema::dropIfExists('cash_closures');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('property_user');

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'meals_and_services',
                'room_inventory_template',
                'cleaning_interval_days',
                'house_rules',
                'deposit_percent',
                'cancellation_policy',
            ]);
            $table->dropConstrainedForeignId('tenant_account_id');
        });

        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('saas_plans');
        Schema::dropIfExists('tenant_accounts');
    }
};
