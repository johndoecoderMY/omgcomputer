<?php

namespace App\Http\Controllers;

use App\Models\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $catalog = ProductService::query();
        $category = $request->input('category');

        if (isset(config('products.categories')[$category])) {
            $catalog->where('category', $category);
        } else {
            $category = '';
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $catalog->where(fn ($query) => $query
                ->where('name', 'like', $search)
                ->orWhere('scope', 'like', $search)
                ->orWhere('audience', 'like', $search));
        }

        return view('products.index', [
            'products' => $catalog->orderBy('name')->get(),
            'categories' => config('products.categories'),
            'selectedCategory' => $category,
        ]);
    }

    public function create(): View
    {
        return view('products_services.create', [
            'product' => new ProductService,
            'categories' => config('products.categories'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['id'] = substr(Str::slug($data['name']), 0, 70).'-'.Str::lower(Str::random(6));
        $product = ProductService::create($data);

        return redirect()->route('products.show', $product)->with('success', 'Product or service created.');
    }

    public function show(ProductService $product): View
    {
        return view('products_services.show', [
            'product' => $product,
            'categoryLabel' => config('products.categories')[$product->category],
        ]);
    }

    public function edit(ProductService $product): View
    {
        return view('products_services.edit', [
            'product' => $product,
            'categories' => config('products.categories'),
        ]);
    }

    public function update(Request $request, ProductService $product): RedirectResponse
    {
        $product->update($this->validatedData($request));

        return redirect()->route('products.show', $product)->with('success', 'Product or service updated.');
    }

    public function delete(ProductService $product): View
    {
        return view('products_services.delete', compact('product'));
    }

    public function destroy(ProductService $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product or service deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(config('products.categories')))],
            'scope' => ['required', 'string', 'max:2000'],
            'audience' => ['required', 'string', 'max:255'],
            'price' => ['required', 'string', 'max:80'],
            'billing_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ]);
    }
}
