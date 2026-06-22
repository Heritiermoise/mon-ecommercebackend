<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('nom', 100)->unique();
                $table->string('slug', 100)->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('couleurs')) {
            Schema::create('couleurs', function (Blueprint $table) {
                $table->id();
                $table->string('nom', 50);
                $table->string('code_hex', 7);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tailles')) {
            Schema::create('tailles', function (Blueprint $table) {
                $table->id();
                $table->string('nom', 20);
                $table->integer('ordre')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('produit_tags')) {
            Schema::create('produit_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
                $table->unique(['produit_id', 'tag_id']);
            });
        }

        if (!Schema::hasTable('produit_couleurs')) {
            Schema::create('produit_couleurs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
                $table->foreignId('couleur_id')->constrained('couleurs')->cascadeOnDelete();
                $table->unique(['produit_id', 'couleur_id']);
            });
        }

        if (!Schema::hasTable('produit_tailles')) {
            Schema::create('produit_tailles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
                $table->foreignId('taille_id')->constrained('tailles')->cascadeOnDelete();
                $table->integer('stock')->default(0);
                $table->unique(['produit_id', 'taille_id']);
            });
        }

        if (!Schema::hasTable('recherches_recentes')) {
            Schema::create('recherches_recentes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('utilisateur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
                $table->string('session_id', 100)->nullable();
                $table->string('terme', 255);
                $table->string('ip_address', 45)->nullable();
                $table->integer('nb_resultats')->default(0);
                $table->timestamps();
                
                $table->index(['terme']);
                $table->index(['utilisateur_id', 'created_at']);
                $table->index(['session_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('produits_vues')) {
            Schema::create('produits_vues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
                $table->foreignId('utilisateur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
                $table->string('session_id', 100)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                
                $table->index(['produit_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('produits_achetes')) {
            Schema::create('produits_achetes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
                $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
                $table->integer('quantite')->default(1);
                $table->decimal('prix_unitaire', 12, 2);
                $table->timestamps();
                
                $table->index(['produit_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('produits_achetes');
        Schema::dropIfExists('produits_vues');
        Schema::dropIfExists('recherches_recentes');
        Schema::dropIfExists('produit_tailles');
        Schema::dropIfExists('produit_couleurs');
        Schema::dropIfExists('produit_tags');
        Schema::dropIfExists('tailles');
        Schema::dropIfExists('couleurs');
        Schema::dropIfExists('tags');
    }
};