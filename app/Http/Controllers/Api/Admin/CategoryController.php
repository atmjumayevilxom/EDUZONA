<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('games')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100', 'unique:categories,name'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] ??= Category::max('sort_order') + 1;
        $validated['status'] = 'active';

        $category = Category::create($validated);

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name'       => ['sometimes', 'string', 'max:100', 'unique:categories,name,' . $id],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status'     => ['sometimes', 'in:active,inactive'],
        ]);

        $category->update($validated);

        return response()->json(['data' => $category]);
    }

    public function destroy(int $id)
    {
        $category = Category::findOrFail($id);

        if ($category->games()->count() > 0) {
            return response()->json(['message' => 'Bu kategoriyada o\'yinlar bor. Avval ularni o\'chiring.'], 422);
        }

        $category->delete();

        return response()->json(['message' => 'O\'chirildi.']);
    }
}
