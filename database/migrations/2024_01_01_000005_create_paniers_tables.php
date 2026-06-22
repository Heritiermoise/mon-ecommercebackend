<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paniers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->enum('statut', ['actif', 'converti', 'abandonne'])->default('actif');
            $table->string('session_id', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('articles_panier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits');
            $table->integer('quantite')->default(1);
            $table->decimal('prix_unitaire', 10, 2);
            $table->timestamps();
            $table->unique(['panier_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles_panier');
        Schema::dropIfExists('paniers');
    }
};