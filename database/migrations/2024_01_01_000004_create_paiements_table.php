<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->cascadeOnDelete();
            $table->enum('methode', ['mpesa', 'orange_money', 'airtel_money', 'carte', 'paypal']);
            $table->string('id_transaction_fournisseur', 255)->nullable();
            $table->decimal('montant', 10, 2);
            $table->decimal('frais', 10, 2)->default(0);
            $table->enum('statut', ['en_attente', 'succes', 'echoue', 'rembourse'])->default('en_attente');
            $table->text('details_paiement')->nullable();
            $table->timestamp('paye_le')->nullable();
            $table->timestamp('rembourse_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};