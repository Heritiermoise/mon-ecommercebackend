<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('nom', 120);
            $table->string('slug', 120)->unique();
            $table->timestamps();
        });

        Schema::create('marques', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120)->unique();
            $table->timestamps();
        });

        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->decimal('prix', 10, 2);
            $table->decimal('prix_remise', 10, 2)->nullable();
            $table->integer('quantite_stock')->default(0);
            $table->foreignId('categorie_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('marque_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->decimal('note_moyenne', 3, 2)->default(0);
            $table->integer('nombre_avis')->default(0);
            $table->timestamps();
        });

        Schema::create('images_produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained()->cascadeOnDelete();
            $table->string('url_image', 255);
            $table->boolean('est_principale')->default(false);
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images_produits');
        Schema::dropIfExists('produits');
        Schema::dropIfExists('marques');
        Schema::dropIfExists('categories');
    }
};