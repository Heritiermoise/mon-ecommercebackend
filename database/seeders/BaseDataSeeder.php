<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Marque;
use Illuminate\Database\Seeder;

class BaseDataSeeder extends Seeder
{
    public function run(): void
    {
        // Catégories
        $categories = [
            ['nom' => 'Smartphones', 'slug' => 'smartphones'],
            ['nom' => 'Ordinateurs', 'slug' => 'ordinateurs'],
            ['nom' => 'Audio', 'slug' => 'audio'],
            ['nom' => 'Accessoires', 'slug' => 'accessoires'],
            ['nom' => 'Gaming', 'slug' => 'gaming'],
            ['nom' => 'Électroménager', 'slug' => 'electromenager'],
            ['nom' => 'Mode', 'slug' => 'mode'],
            ['nom' => 'Sport', 'slug' => 'sport'],
        ];

        foreach ($categories as $cat) {
            Categorie::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Marques
        $marques = [
            ['nom' => 'Apple'],
            ['nom' => 'Samsung'],
            ['nom' => 'Sony'],
            ['nom' => 'Microsoft'],
            ['nom' => 'Asus'],
            ['nom' => 'Dell'],
            ['nom' => 'HP'],
            ['nom' => 'Lenovo'],
            ['nom' => 'Nike'],
            ['nom' => 'Adidas'],
        ];

        foreach ($marques as $marque) {
            Marque::firstOrCreate(['nom' => $marque['nom']], $marque);
        }

        $this->command->info('✅ Catégories et marques créées');
    }
}