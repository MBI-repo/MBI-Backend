<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductsController extends Controller
{
    /**
     * List all products (public).
     */
    public function index()
    {
        $products = Product::with('images')->orderByDesc('id')->where('product_type','product')->get();
        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $data = $products->map(function (Product $p) use ($baseUrl) {
            return [
                'id'          => $p->id,
                'uuid'        => $p->uuid,
                'name'        => $p->name,
                'description' => $p->description,
                'category'    => $p->category,
                'price'       => $p->price,
                'waranty'     => $p->waranty,
                'product_type'=> $p->product_type,
                'created_by'  => $p->created_by,
                'images'      => $p->images->map(fn ($img) => [
                    'id'        => $img->id,
                    'image_url' => $baseUrl . $img->image_url,
                    'sort_order'=> $img->sort_order,
                ]),
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }
    public function donationProduct()
    {
        $products = Product::with('images')->orderByDesc('id')->where('product_type','donation')->get();
        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $product_url = 'https://portal.mybridgeinternational.org/mbi-portal-files/public/';
        $data = $products->map(function (Product $p) use ($baseUrl,$product_url) {
            return [
                'id'          => $p->id,
                'uuid'        => $p->uuid,
                'name'        => $p->name,
                'description' => $p->description,
                'category'    => $p->category,
                'price'       => $p->price,
                'waranty'     => $p->waranty,
                'product_type'=> $p->product_type,
                'created_by'  => $p->created_by,
                'images'      => $p->images->map(fn ($img) => [
                    'id'        => $img->id,
                    'image_url' => $p->product_type === 'donation' ? $baseUrl . $img->image_url : $product_url . $img->image_url,
                    'sort_order'=> $img->sort_order,
                ]),
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }

    /**
     * View a single product (public).
     */
    public function show($id)
    {
        $product = is_numeric($id)
            ? Product::with('images')->find($id)
            : Product::with('images')->where('uuid', $id)->first();

        if (! $product) {
            return response()->json([
                'status' => false,
                'message'=> 'Product not found',
            ], 404);
        }

        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $productUrl = 'https://portal.mybridgeinternational.org/mbi-portal-files/public/';
        $data = [
            'id'          => $product->id,
            'uuid'        => $product->uuid,
            'name'        => $product->name,
            'description' => $product->description,
            'category'    => $product->category,
            'price'       => $product->price,
            'waranty'     => $product->waranty,
            'product_type'=> $product->product_type,
            'created_by'  => $product->created_by,
            'images'      => $product->images->map(fn ($img) => [
                'id'        => $img->id,
                'image_url' => $product->product_type === 'donation' ? $baseUrl . $img->image_url : $productUrl . $img->image_url,
                'sort_order'=> $img->sort_order,
            ]),
        ];

        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }

    /**
     * Create a product (seller only).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        if ($user->category !== 'seller') {
            return response()->json(['status' => false, 'message' => 'Only sellers can add products'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'category'    => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'waranty'     => ['required', 'string', 'max:255'],

            // Optional fields
            'discount_code'            => ['nullable', 'string', 'max:255'],
            'ukca_mark'                => ['nullable', 'string', 'max:255'],
            'manufacturer'             => ['nullable', 'string', 'max:255'],
            'model_number'             => ['nullable', 'string', 'max:255'],
            'condition'                => ['nullable', 'string', 'max:255'],
            'age_of_equipment'         => ['nullable', 'string', 'max:255'],
            'last_serviced_date'       => ['nullable', 'date'],
            'known_issues'             => ['nullable', 'boolean'],
            'known_issues_details'     => ['nullable', 'string'],
            'accessories'              => ['nullable', 'string'],
            'pickup_available_date'    => ['nullable', 'date'],
            'equipment_location'       => ['nullable', 'string', 'max:255'],
            'shipping_cost_contribution'=> ['nullable', 'string', 'max:255'],

            // Photos
            'photos'                    => ['nullable', 'array'],
            'photos.*'                  => ['file', 'image', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }
        $validated = $validator->validated();

        $product = new Product();
        $product->fill($validated);
        $product->product_type = 'product';
        $product->created_by   = $user->id;
        $product->save();

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $idx => $file) {
                $path = $file->store('product_images', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url'  => $path,
                    'sort_order' => $idx,
                ]);
            }
        }

        return $this->show($product->id);
    }

    /**
     * Update a product (seller owner only).
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $product = Product::where('uuid',$id)->first();
        if (! $product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }
        if ($user->category !== 'seller' || $product->created_by !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'        => ['sometimes', 'string', 'max:255'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'category'    => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'waranty'     => ['sometimes', 'string', 'max:255'],

            'discount_code'            => ['nullable', 'string', 'max:255'],
            'ukca_mark'                => ['nullable', 'string', 'max:255'],
            'manufacturer'             => ['nullable', 'string', 'max:255'],
            'model_number'             => ['nullable', 'string', 'max:255'],
            'condition'                => ['nullable', 'string', 'max:255'],
            'age_of_equipment'         => ['nullable', 'string', 'max:255'],
            'last_serviced_date'       => ['nullable', 'date'],
            'known_issues'             => ['nullable', 'boolean'],
            'known_issues_details'     => ['nullable', 'string'],
            'accessories'              => ['nullable', 'string'],
            'pickup_available_date'    => ['nullable', 'date'],
            'equipment_location'       => ['nullable', 'string', 'max:255'],
            'shipping_cost_contribution'=> ['nullable', 'string', 'max:255'],

            'photos'                    => ['nullable', 'array'],
            'photos.*'                  => ['file', 'image', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }
        $validated = $validator->validated();

        $product->fill($validated);
        $product->save();

        if ($request->hasFile('photos')) {
            $currentCount = ProductImage::where('product_id', $product->id)->count();
            foreach ($request->file('photos') as $idx => $file) {
                $path = $file->store('product_images', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url'  => $path,
                    'sort_order' => $currentCount + $idx,
                ]);
            }
        }

        return $this->show($product->id);
    }

    /**
     * Delete a product and its images (seller owner only).
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $product = Product::with('images')->where('uuid',$id)->first();
        if (! $product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }
        if ($user->category !== 'seller' || $product->created_by !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        // delete images files and records
        foreach ($product->images as $img) {
            if ($img->image_url && Storage::disk('public')->exists($img->image_url)) {
                Storage::disk('public')->delete($img->image_url);
            }
            $img->delete();
        }

        $product->delete();

        return response()->json(['status' => true, 'message' => 'Product deleted']);
    }

    /**
     * Delete a single product image (seller owner only).
     */
    public function destroyImage($imageId)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $image = ProductImage::find($imageId);
        if (! $image) {
            return response()->json(['status' => false, 'message' => 'Image not found'], 404);
        }

        $product = Product::find($image->product_id);
        if (! $product || $product->created_by !== $user->id || $user->category !== 'seller') {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        if ($image->image_url && Storage::disk('public')->exists($image->image_url)) {
            Storage::disk('public')->delete($image->image_url);
        }
        $image->delete();

        return response()->json(['status' => true, 'message' => 'Image deleted']);
    }
}
