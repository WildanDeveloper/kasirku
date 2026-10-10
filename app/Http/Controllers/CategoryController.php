<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:categories,name']]);

        Category::create($data);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
