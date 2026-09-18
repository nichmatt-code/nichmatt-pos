<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->where('is_active', true)
            ->when($request->string('search')->trim()->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->integer('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->paginate(40);

        return ProductResource::collection($products);
    }
}
