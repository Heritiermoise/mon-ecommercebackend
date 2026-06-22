<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function publicIndex()
    {
        return response()->json(['success' => true, 'data' => Categorie::all()]);
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => Categorie::withCount('produits')->get()]);
    }

    public function store(Request $request)
    {
        $request->validate(['nom' => 'required|string|max:120', 'slug' => 'required|string|unique:categories,slug']);
        $categorie = Categorie::create($request->all());
        return response()->json(['success' => true, 'data' => $categorie], 201);
    }

    public function show($id)
    {
        return response()->json(['success' => true, 'data' => Categorie::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $categorie = Categorie::findOrFail($id);
        $categorie->update($request->all());
        return response()->json(['success' => true, 'data' => $categorie]);
    }

    public function destroy($id)
    {
        Categorie::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Catégorie supprimée']);
    }
}