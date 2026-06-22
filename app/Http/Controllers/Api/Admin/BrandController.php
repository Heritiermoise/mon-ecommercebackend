<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marque;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function publicIndex()
    {
        return response()->json(['success' => true, 'data' => Marque::all()]);
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => Marque::withCount('produits')->get()]);
    }

    public function store(Request $request)
    {
        $request->validate(['nom' => 'required|string|unique:marques,nom']);
        $marque = Marque::create($request->all());
        return response()->json(['success' => true, 'data' => $marque], 201);
    }

    public function show($id)
    {
        return response()->json(['success' => true, 'data' => Marque::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $marque = Marque::findOrFail($id);
        $marque->update($request->all());
        return response()->json(['success' => true, 'data' => $marque]);
    }

    public function destroy($id)
    {
        Marque::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Marque supprimée']);
    }
}