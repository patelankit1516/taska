<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display the product listing page
     */
    public function index()
    {
        return view('products.index');
    }

    /**
     * Get all products with their images for API (with server-side pagination for AG Grid)
     */
    public function list(Request $request)
    {
        // AG Grid server-side pagination parameters
        $startRow = $request->input('startRow', 0);
        $endRow = $request->input('endRow', 100);
        $pageSize = $endRow - $startRow;
        
        // Get sorting parameters
        $sortModel = $request->input('sortModel', []);
        $sortBy = 'created_at';
        $sortOrder = 'desc';
        
        if (!empty($sortModel)) {
            $sortBy = $sortModel[0]['colId'] ?? 'created_at';
            $sortOrder = $sortModel[0]['sort'] ?? 'desc';
        }
        
        // Get filtering parameters
        $filterModel = $request->input('filterModel', []);

        // Build query
        $query = Product::with(['images' => function($q) {
            $q->where('variant', '256px')->orderBy('sort_order', 'asc')->limit(1);
        }]);
        
        // Apply filters
        foreach ($filterModel as $field => $filter) {
            if (isset($filter['filter'])) {
                $value = $filter['filter'];
                $type = $filter['type'] ?? 'contains';
                
                switch ($type) {
                    case 'contains':
                        $query->where($field, 'like', "%{$value}%");
                        break;
                    case 'equals':
                        $query->where($field, '=', $value);
                        break;
                    case 'startsWith':
                        $query->where($field, 'like', "{$value}%");
                        break;
                    case 'endsWith':
                        $query->where($field, 'like', "%{$value}");
                        break;
                }
            }
        }
        
        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Get total count before pagination
        $total = $query->count();
        
        // Apply pagination with optimized image loading
        $products = $query
            ->with(['images' => function ($query) {
                $query->orderBy('sort_order')->limit(1);
            }])
            ->withCount('images')
            ->skip($startRow)
            ->take($pageSize)
            ->get()
            ->map(function ($product) {
                $firstImage = $product->images->first();
                
                // Fix image path: replace 'public/' with 'storage/' for asset URL
                $imagePath = null;
                if ($firstImage) {
                    $imagePath = str_replace('public/', 'storage/', $firstImage->path);
                }
                
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price ? (float)$product->price : 0,
                    'price_display' => $product->price ? '$' . number_format((float)$product->price, 2) : '$0.00',
                    'description' => $product->description,
                    'stock' => $product->stock,
                    'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
                    'image' => $imagePath ? asset($imagePath) : null,
                    'image_count' => $product->images_count,
                ];
            });

        return response()->json([
            'success' => true,
            'rowData' => $products,
            'rowCount' => $total,
        ]);
    }

    /**
     * Get single product details
     */
    public function show($id)
    {
        $product = Product::with('images.upload')->findOrFail($id);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'description' => $product->description,
                'stock' => $product->stock,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->url,
                        'thumbnail_url' => $image->thumbnail_url,
                        'small_url' => $image->small_url,
                        'medium_url' => $image->medium_url,
                        'large_url' => $image->large_url,
                        'alt_text' => $image->alt_text,
                        'sort_order' => $image->sort_order,
                    ];
                })->sortBy('sort_order')->values(),
            ],
        ]);
    }
}
