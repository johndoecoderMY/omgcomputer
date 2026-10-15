<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $catalog = collect(config('products.catalog'));
        $category = $request->input('category');

        if (isset(config('products.categories')[$category])) {
            $catalog = $catalog->where('category', $category);
        } else {
            $category = '';
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->string('search')->toString());
            $catalog = $catalog->filter(fn (array $product): bool => str_contains(
                mb_strtolower(implode(' ', [$product['name'], $product['scope'], $product['audience']])),
                $search
            ));
        }

        return view('products.index', [
            'products' => $catalog->values(),
            'categories' => config('products.categories'),
            'selectedCategory' => $category,
        ]);
    }
}