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
            $table->foreignId('property_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->foreignId('property_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $propertyId = DB::table('properties')->orderBy('id')->value('id');

        if ($propertyId) {
            DB::table('users')->whereNull('property_id')->update(['property_id' => $propertyId]);
            DB::table('guests')->whereNull('property_id')->update(['property_id' => $propertyId]);
        }
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('property_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('property_id');
        });
    }
};
