<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wishlists')) {
            Schema::create('wishlists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
                $table->string('nom_collection', 100)->default('Mes favoris');
                $table->text('note_personnelle')->nullable();
                $table->boolean('alerte_prix')->default(false);
                $table->decimal('prix_cible', 12, 2)->nullable();
                $table->decimal('prix_ajout', 12, 2)->nullable();
                $table->timestamp('derniere_alerte')->nullable();
                $table->timestamps();
                
                $table->unique(['utilisateur_id', 'produit_id']);
                $table->index(['utilisateur_id', 'created_at']);
                $table->index(['alerte_prix', 'prix_cible']);
            });
        }

        if (!Schema::hasTable('wishlist_alertes')) {
            Schema::create('wishlist_alertes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wishlist_id')->constrained('wishlists')->cascadeOnDelete();
                $table->decimal('ancien_prix', 12, 2);
                $table->decimal('nouveau_prix', 12, 2);
                $table->decimal('pourcentage_reduction', 5, 2)->default(0);
                $table->boolean('est_lue')->default(false);
                $table->timestamps();
                
                $table->index(['wishlist_id', 'est_lue']);
            });
        }

        if (!Schema::hasTable('wishlist_partagees')) {
            Schema::create('wishlist_partagees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->string('token', 64)->unique();
                $table->string('nom', 100)->default('Ma wishlist');
                $table->boolean('est_publique')->default(true);
                $table->timestamp('expire_le')->nullable();
                $table->integer('nb_vues')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_partagees');
        Schema::dropIfExists('wishlist_alertes');
        Schema::dropIfExists('wishlists');
    }
};