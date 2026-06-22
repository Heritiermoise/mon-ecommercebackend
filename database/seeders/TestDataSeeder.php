<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Marque;
use App\Models\Produit;
use App\Models\ImageProduit;
use App\Models\CodePromo;
use App\Models\ParametreSite;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Catégories (firstOrCreate pour éviter les doublons)
        $cat1 = Categorie::firstOrCreate(['slug' => 'smartphones'], ['nom' => 'Smartphones']);
        $cat2 = Categorie::firstOrCreate(['slug' => 'ordinateurs'], ['nom' => 'Ordinateurs']);
        $cat3 = Categorie::firstOrCreate(['slug' => 'audio'], ['nom' => 'Audio']);
        $cat4 = Categorie::firstOrCreate(['slug' => 'accessoires'], ['nom' => 'Accessoires']);
        $cat5 = Categorie::firstOrCreate(['slug' => 'gaming'], ['nom' => 'Gaming']);

        // Marques
        $m1 = Marque::firstOrCreate(['nom' => 'Apple']);
        $m2 = Marque::firstOrCreate(['nom' => 'Samsung']);
        $m3 = Marque::firstOrCreate(['nom' => 'Sony']);
        $m4 = Marque::firstOrCreate(['nom' => 'Microsoft']);
        $m5 = Marque::firstOrCreate(['nom' => 'Asus']);

        // Produits
        $produits = [
            ['nom' => 'iPhone 15 Pro Max', 'slug' => 'iphone-15-pro-max', 'description' => 'Le dernier iPhone avec puce A17 Pro', 'prix' => 1299.99, 'prix_remise' => 1199.99, 'stock' => 50, 'cat' => $cat1->id, 'marque' => $m1->id],
            ['nom' => 'Samsung Galaxy S24 Ultra', 'slug' => 'galaxy-s24-ultra', 'description' => 'Smartphone Android premium', 'prix' => 1399.99, 'prix_remise' => 1299.99, 'stock' => 40, 'cat' => $cat1->id, 'marque' => $m2->id],
            ['nom' => 'MacBook Pro 14"', 'slug' => 'macbook-pro-14', 'description' => 'Ordinateur portable professionnel', 'prix' => 1999.99, 'prix_remise' => null, 'stock' => 25, 'cat' => $cat2->id, 'marque' => $m1->id],
            ['nom' => 'AirPods Pro 2', 'slug' => 'airpods-pro-2', 'description' => 'Écouteurs sans fil avec réduction de bruit', 'prix' => 279.99, 'prix_remise' => 249.99, 'stock' => 100, 'cat' => $cat3->id, 'marque' => $m1->id],
            ['nom' => 'Sony WH-1000XM5', 'slug' => 'sony-wh-1000xm5', 'description' => 'Casque audio premium', 'prix' => 399.99, 'prix_remise' => null, 'stock' => 30, 'cat' => $cat3->id, 'marque' => $m3->id],
            ['nom' => 'Surface Pro 9', 'slug' => 'surface-pro-9', 'description' => 'Tablette PC hybride', 'prix' => 1099.99, 'prix_remise' => 999.99, 'stock' => 20, 'cat' => $cat2->id, 'marque' => $m4->id],
            ['nom' => 'ROG Zephyrus G14', 'slug' => 'rog-zephyrus-g14', 'description' => 'Laptop gaming puissant', 'prix' => 1599.99, 'prix_remise' => null, 'stock' => 15, 'cat' => $cat5->id, 'marque' => $m5->id],
            ['nom' => 'iPad Pro 12.9"', 'slug' => 'ipad-pro-12-9', 'description' => 'Tablette professionnelle', 'prix' => 1299.99, 'prix_remise' => 1199.99, 'stock' => 35, 'cat' => $cat2->id, 'marque' => $m1->id],
        ];

        foreach ($produits as $p) {
            $produit = Produit::firstOrCreate(
                ['slug' => $p['slug']],
                [
                    'nom' => $p['nom'],
                    'description' => $p['description'],
                    'prix' => $p['prix'],
                    'prix_remise' => $p['prix_remise'],
                    'quantite_stock' => $p['stock'],
                    'categorie_id' => $p['cat'],
                    'marque_id' => $p['marque'],
                    'statut' => 'actif',
                ]
            );

            ImageProduit::firstOrCreate(
                ['produit_id' => $produit->id],
                [
                    'url_image' => 'https://via.placeholder.com/600x600?text=' . urlencode($p['nom']),
                    'est_principale' => true,
                ]
            );
        }

        // Codes promo
        CodePromo::firstOrCreate(
            ['code' => 'BIENVENUE10'],
            ['description' => '10% de réduction', 'type_reduction' => 'pourcentage', 'valeur_reduction' => 10, 'montant_minimum' => 50, 'statut' => 'actif', 'utilisation_max' => 1000]
        );
        CodePromo::firstOrCreate(
            ['code' => 'PROMO20'],
            ['description' => '20€ de réduction', 'type_reduction' => 'montant_fixe', 'valeur_reduction' => 20, 'montant_minimum' => 100, 'statut' => 'actif', 'utilisation_max' => 500]
        );

        // Paramètres
        ParametreSite::updateOrCreate(['cle' => 'site_nom'], ['valeur' => 'ShopPro', 'type' => 'string', 'categorie' => 'general']);
        ParametreSite::updateOrCreate(['cle' => 'frais_livraison'], ['valeur' => '9.99', 'type' => 'number', 'categorie' => 'livraison']);
        ParametreSite::updateOrCreate(['cle' => 'livraison_gratuite_seuil'], ['valeur' => '100', 'type' => 'number', 'categorie' => 'livraison']);

        $this->command->info('✅ Données de test synchronisées avec succès !');
    }
}