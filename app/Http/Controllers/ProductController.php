<?php

namespace App\Http\Controllers;

use App\Events\ProductUpdatedEvent;
use App\Models\Product;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{

    public function index()
    {
        return Product::all();
    }

    public function store(Request $request)
    {
        $product = Product::create($request->only('title', 'description', 'image', 'price'));

        event(new ProductUpdatedEvent);

        return response($product, Response::HTTP_CREATED);
    }

    public function show(Product $product)
    {
        return $product;
    }

    public function update(Request $request, Product $product)
    {
        $product->update($request->only('title', 'description', 'image', 'price'));

        event(new ProductUpdatedEvent);

        return response($product, Response::HTTP_ACCEPTED);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        event(new ProductUpdatedEvent);

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function frontend()
    {
        #Si los productos están cacheados los devuelvo con redis, así no hago peticiones a la BD
        if ($products = \Cache::get('products_frontend')) {
            return $products;
        }

        $products = Product::all();
        # Si no están en la cache los ponemos
        \Cache::set('products_frontend', $products, 30 * 60); // 30min

        return $products;
    }

    public function backend(Request $request)
    {
        $page = $request->input('page', 1); # Pagina 1 por defecto
        # Si existe la cache retorna la caché, si no retorna de la BD
        $products = \Cache::remember('products_backend', 30 * 60, fn () => Product::all());

        # Buscar producto
        if ($s = $request->input('s')) {
            $products = $products
                ->filter(
                    fn (Product $product) => Str::contains($product->title, $s) || Str::contains($product->description, $s)
                );
        }

        # Contar los productos
        $total = $products->count();

        # Order productos
        if ($sort = $request->input('sort')) {
            if ($sort === 'asc') {
                $products = $products->sortBy([
                    fn ($a, $b) => $a['price'] <=> $b['price'] // devolver -1 si b>a, 0 si b=a, 1 si b<a
                ]);
            } else if ($sort === 'desc') {
                $products = $products->sortBy([
                    fn ($a, $b) => $b['price'] <=> $a['price'] // devolver -1 si b<a, 0 si b=a, 1 si b>a
                ]);
            }
        }

        return [
            'data' => $products->forPage($page, 9)->values(), # Paginar por cada 9 productos
            'meta' => [
                'total' => $total,
                'page' => $page,
                'last_page' => ceil($total / 9) # Ceil para devolver un entero
            ]
        ];
    }
}
