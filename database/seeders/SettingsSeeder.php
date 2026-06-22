<?php

namespace Database\Seeders;

use App\Models\ParametreSite;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $parametres = [
            ['cle' => 'site_nom', 'valeur' => 'ShopPro', 'type' => 'string', 'categorie' => 'general'],
            ['cle' => 'site_description', 'valeur' => 'La meilleure boutique en ligne', 'type' => 'string', 'categorie' => 'general'],
            ['cle' => 'site_email_contact', 'valeur' => 'contact@shoppro.com', 'type' => 'string', 'categorie' => 'contact'],
            ['cle' => 'site_telephone', 'valeur' => '+243 000 000 000', 'type' => 'string', 'categorie' => 'contact'],
            ['cle' => 'devise_principale', 'valeur' => 'USD', 'type' => 'string', 'categorie' => 'devise'],
            ['cle' => 'taux_change_cdf', 'valeur' => '2800', 'type' => 'number', 'categorie' => 'devise'],
            ['cle' => 'frais_livraison', 'valeur' => '9.99', 'type' => 'number', 'categorie' => 'livraison'],
            ['cle' => 'livraison_gratuite_seuil', 'valeur' => '100', 'type' => 'number', 'categorie' => 'livraison'],
            ['cle' => 'points_par_euro', 'valeur' => '1', 'type' => 'number', 'categorie' => 'fidelite'],
        ];

        foreach ($parametres as $param) {
            ParametreSite::updateOrCreate(
                ['cle' => $param['cle']],
                $param
            );
        }

        $this->command->info('✅ Paramètres du site créés');
    }
}