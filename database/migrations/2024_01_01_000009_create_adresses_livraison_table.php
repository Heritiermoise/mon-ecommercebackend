<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('adresses_livraison')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Schema::dropIfExists('adresses_livraison');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        
        Schema::create('adresses_livraison', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->string('nom_complet', 150);
            $table->string('telephone', 20);
            $table->text('adresse');
            $table->string('ville', 100);
            $table->string('code_postal', 20)->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('est_defaut')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('adresses_livraison');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};