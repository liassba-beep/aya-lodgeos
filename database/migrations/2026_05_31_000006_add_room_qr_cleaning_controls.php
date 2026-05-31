<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('qr_code')->nullable()->after('status');
        });

        DB::table('rooms')
            ->whereNull('qr_code')
            ->orderBy('id')
            ->get(['id', 'property_id'])
            ->each(function ($room): void {
                DB::table('rooms')
                    ->where('id', $room->id)
                    ->update(['qr_code' => 'LODGEOS-ROOM-'.$room->property_id.'-'.$room->id]);
            });

        Schema::table('rooms', function (Blueprint $table) {
            $table->unique(['property_id', 'qr_code']);
        });

        Schema::table('daily_checklists', function (Blueprint $table) {
            $table->foreignId('room_id')
                ->nullable()
                ->after('staff_member_id')
                ->constrained('rooms')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_checklists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique(['property_id', 'qr_code']);
            $table->dropColumn('qr_code');
        });
    }
};
