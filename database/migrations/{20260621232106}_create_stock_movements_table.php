<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mouvements_stock')) {
            Schema::create('mouvements_stock', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
                $table->foreignId('utilisateur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
                $table->enum('type', ['entree', 'sortie', 'ajustement', 'vente', 'retour']);
                $table->integer('quantite');
                $table->integer('stock_avant');
                $table->integer('stock_apres');
                $table->string('reference', 100)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                
                $table->index(['produit_id', 'created_at']);
                $table->index(['type', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
    }
};