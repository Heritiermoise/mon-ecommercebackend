<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Categorie;
use App\Models\Marque;
use App\Models\Produit;
use App\Models\ImageProduit;
use App\Models\CodePromo;
use App\Models\ParametreSite;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer les utilisateurs
        $admin = User::create([
            'nom' => 'Admin Super',
            'email' => 'admin@shoppro.com',
            'telephone' => '+33612345678',
            'mot_de_passe_hash' => Hash::make('admin123'),
            'role' => 'super_administrateur',
            'statut' => 'actif',
        ]);

        $client = User::create([
            'nom' => 'Jean Client',
            'email' => 'client@shoppro.com',
            'telephone' => '+33698765432',
            'mot_de_passe_hash' => Hash::make('client123'),
            'role' => 'client',
            'statut' => 'actif',
        ]);

        $this->command->info('✅ Utilisateurs créés');

        // 2. Créer les catégories
        $categories = [
            ['nom' => 'Smartphones', 'slug' => 'smartphones'],
            ['nom' => 'Ordinateurs', 'slug' => 'ordinateurs'],
            ['nom' => 'Audio', 'slug' => 'audio'],
            ['nom' => 'Accessoires', 'slug' => 'accessoires'],
            ['nom' => 'Gaming', 'slug' => 'gaming'],
        ];

        foreach ($categories as $cat) {
            Categorie::create($cat);
        }

        $this->command->info('✅ Catégories créées');

        // 3. Créer les marques
        $marques = [
            ['nom' => 'Apple'],
            ['nom' => 'Samsung'],
            ['nom' => 'Sony'],
            ['nom' => 'Microsoft'],
            ['nom' => 'Asus'],
        ];

        foreach ($marques as $marque) {
            Marque::create($marque);
        }

        $this->command->info('✅ Marques créées');

        // 4. Créer les produits
        $produits = [
            [
                'nom' => 'iPhone 15 Pro Max',
                'description' => 'Le dernier iPhone avec puce A17 Pro',
                'prix' => 1299.99,
                'prix_remise' => 1199.99,
                'quantite_stock' => 50,
                'categorie_id' => 1,
                'marque_id' => 1,
            ],
            [
                'nom' => 'Samsung Galaxy S24 Ultra',
                'description' => 'Smartphone Android premium',
                'prix' => 1399.99,
                'prix_remise' => 1299.99,
                'quantite_stock' => 40,
                'categorie_id' => 1,
                'marque_id' => 2,
            ],
            [
                'nom' => 'MacBook Pro 14',
                'description' => 'Ordinateur portable professionnel',
                'prix' => 1999.99,
                'prix_remise' => null,
                'quantite_stock' => 25,
                'categorie_id' => 2,
                'marque_id' => 1,
            ],
            [
                'nom' => 'AirPods Pro 2',
                'description' => 'Écouteurs sans fil avec réduction de bruit',
                'prix' => 279.99,
                'prix_remise' => 249.99,
                'quantite_stock' => 100,
                'categorie_id' => 3,
                'marque_id' => 1,
            ],
            [
                'nom' => 'Sony WH-1000XM5',
                'description' => 'Casque audio premium',
                'prix' => 399.99,
                'prix_remise' => null,
                'quantite_stock' => 30,
                'categorie_id' => 3,
                'marque_id' => 3,
            ],
        ];

        foreach ($produits as $prod) {
            $slug = Str::slug($prod['nom']) . '-' . uniqid();
            $produit = Produit::create(array_merge($prod, ['slug' => $slug]));

            ImageProduit::create([
                'produit_id' => $produit->id,
                'url_image' => 'https://via.placeholder.com/600x600?text=' . urlencode($prod['nom']),
                'est_principale' => true,
                'ordre' => 0,
            ]);
        }

        $this->command->info('✅ Produits créés');

        // 5. Créer les codes promo
        CodePromo::create([
            'code' => 'SHOPPRO10',
            'description' => '10% de réduction',
            'type_reduction' => 'pourcentage',
            'valeur_reduction' => 10,
            'montant_minimum' => 50,
            'statut' => 'actif',
            'utilisation_max' => 100,
            'utilisation_par_user' => 1,
        ]);

        CodePromo::create([
            'code' => 'BIENVENUE10',
            'description' => '10€ de réduction',
            'type_reduction' => 'montant_fixe',
            'valeur_reduction' => 10,
            'montant_minimum' => 30,
            'statut' => 'actif',
            'utilisation_max' => 50,
            'utilisation_par_user' => 1,
        ]);

        $this->command->info('✅ Codes promo créés');

        // 6. Créer les paramètres du site
        $parametres = [
            ['cle' => 'site_nom', 'valeur' => 'ShopPro', 'type' => 'string', 'categorie' => 'general'],
            ['cle' => 'frais_livraison', 'valeur' => '9.99', 'type' => 'number', 'categorie' => 'livraison'],
            ['cle' => 'livraison_gratuite_seuil', 'valeur' => '100', 'type' => 'number', 'categorie' => 'livraison'],
        ];

        foreach ($parametres as $param) {
            ParametreSite::create($param);
        }

        $this->command->info('✅ Paramètres créés');
        $this->command->info('🎉 SEEDING TERMINÉ !');
    }
}