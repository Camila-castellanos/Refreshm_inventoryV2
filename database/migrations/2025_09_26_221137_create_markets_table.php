<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            
            // Relación con la shop interna del negocio
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            
            // Información básica del ecommerce
            $table->string('name'); // Nombre público de la tienda
            $table->string('slug')->unique(); // URL amigable
            $table->text('description')->nullable(); // Descripción de la tienda
            $table->string('tagline')->nullable(); // Eslogan
            
            // Configuración visual
            $table->string('logo_url')->nullable(); // URL del logo
            $table->string('banner_url')->nullable(); // Banner principal
            $table->json('theme_colors')->nullable(); // Colores del tema
            
            // Configuración del ecommerce
            $table->boolean('is_active')->default(true); // Tienda activa/inactiva
            $table->boolean('show_inventory_count')->default(false); // Mostrar cantidad en stock
            $table->string('currency', 3)->default('USD'); // Moneda
            $table->decimal('tax_rate', 5, 4)->default(0); // Tasa de impuesto
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            
            // Configuración de contacto
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            
            // Políticas de la tienda
            $table->text('return_policy')->nullable();
            $table->text('shipping_policy')->nullable();
            $table->text('privacy_policy')->nullable();
            
            $table->timestamps();
            
            // Indices
            $table->index(['slug', 'is_active']);
            $table->index('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
