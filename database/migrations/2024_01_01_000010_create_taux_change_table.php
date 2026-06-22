<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('taux_change');
        
        Schema::create('taux_change', function (Blueprint $table) {
            $table->id();
            $table->string('devise_source', 10)->default('USD');
            $table->string('devise_cible', 10)->default('CDF');
            $table->decimal('taux', 15, 4)->default(2800.0000);
            $table->boolean('est_actif')->default(true);
            $table->timestamp('date_application')->useCurrent();
            $table->text('note')->nullable();
            $table->foreignId('modifie_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('taux_change')->insert([
            'devise_source' => 'USD',
            'devise_cible' => 'CDF',
            'taux' => 2800.0000,
            'est_actif' => true,
            'date_application' => now(),
            'note' => 'Taux initial',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('taux_change');
    }
};