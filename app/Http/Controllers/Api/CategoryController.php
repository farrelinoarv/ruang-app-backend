<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Get all categories.
     */
    public function index(): JsonResponse
    {
        $categories = Category::select('id', 'name', 'slug')
            ->withCount('campaigns')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ], 200);
    }
}
