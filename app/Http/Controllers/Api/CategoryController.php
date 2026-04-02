<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('status', 'active')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'sort_order']);

        return response()->json(['data' => $categories]);
    }
}
