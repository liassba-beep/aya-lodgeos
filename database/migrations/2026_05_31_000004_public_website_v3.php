<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_accounts', function (Blueprint $table) {
            $table->string('whatsapp_number', 32)->nullable()->after('billing_phone');
            $table->decimal('latitude', 10, 7)->nullable()->after('whatsapp_number');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('address_label')->nullable()->after('longitude');
            $table->text('directions_note')->nullable()->after('address_label');
            $table->json('nearby_json')->nullable()->after('directions_note');
            $table->string('seo_title')->nullable()->after('nearby_json');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->string('og_image')->nullable()->after('seo_description');
            $table->string('favicon_path')->nullable()->after('og_image');
        });

        Schema::create('property_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenant_accounts')->cascadeOnDelete();
            $table->string('path');
            $table->string('alt');
            $table->string('caption')->nullable();
            $table->string('category')->default('exterior');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenant_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('capacity')->default(2);
            $table->decimal('price_from', 12, 2)->default(0);
            $table->json('amenities_json')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenant_accounts')->cascadeOnDelete();
            $table->string('author');
            $table->text('text');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('source')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('property_photos');

        Schema::table('tenant_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_number',
                'latitude',
                'longitude',
                'address_label',
                'directions_note',
                'nearby_json',
                'seo_title',
                'seo_description',
                'og_image',
                'favicon_path',
            ]);
        });
    }
};
