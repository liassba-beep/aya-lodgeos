<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('stock_item_id')->nullable()->after('reference')->constrained()->nullOnDelete();
            $table->decimal('stock_quantity', 12, 2)->nullable()->after('stock_item_id');
            $table->decimal('stock_unit_cost', 12, 2)->nullable()->after('stock_quantity');
            $table->foreignId('stock_movement_id')->nullable()->after('stock_unit_cost')->constrained()->nullOnDelete();
        });

        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_member_id')->constrained()->cascadeOnDelete();
            $table->date('schedule_month');
            $table->date('shift_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('shift_type')->default('normal');
            $table->string('status')->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_schedules');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_movement_id');
            $table->dropColumn('stock_unit_cost');
            $table->dropColumn('stock_quantity');
            $table->dropConstrainedForeignId('stock_item_id');
        });
    }
};
