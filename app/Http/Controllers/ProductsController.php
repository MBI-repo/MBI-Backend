<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBidding;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    /**
     * List all products (public).
     */
    public function index()
    {
         $products = Product::with('images')
        ->where('product_type', 'product')
        ->where('state', 'show')
        ->where('stock', '>', 0)
        ->orderByDesc('id')
        ->get();
        //$products = Product::with('images')->orderByDesc('id')->where('product_type','product')->get();
        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $productUrl = 'https://portal.mybridgeinternational.org/mbi-portal-files/public/';
        


        $data = $products->map(function (Product $p) use ($baseUrl, $productUrl) {   
            return [
                'id'          => $p->id,
                'uuid'        => $p->uuid,
                'name'        => $p->name,
                'description' => $p->description,
                'category'    => $p->category,
                'price'       => $p->price,
                'warranty'    => $p->warranty,
                'state'       => $p->state,
                'product_type'=> $p->product_type,
                'created_by'  => $p->created_by,
                'images'      => $p->images->map(fn ($img) => [
                    'id'        => $img->id,
                    'image_url' => Storage::disk('public')->exists($img->image_url) ? $baseUrl . $img->image_url : $productUrl . $img->image_url,
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
        $baseUrl = 'https://admin.mybridgeinternational.org/mbi-admin-files/public/';
        $data = $products->map(function (Product $p) use ($baseUrl) {
            return [
                'id'          => $p->id,
                'uuid'        => $p->uuid,
                'name'        => $p->name,
                'description' => $p->description,
                'category'    => $p->category,
                'price'       => $p->price,
                'warranty'    => $p->warranty,
                'product_type'=> $p->product_type,
                'created_by'  => $p->created_by,
                'images'      => $p->images->map(fn ($img) => [
                    'id'        => $img->id,
                    'image_url' => rtrim($baseUrl, '/') . '/' . ltrim($img->image_url, '/'),
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
        $query = Product::with('images')
            ->where(function($q) {
                $q->where('product_type', 'donation')
                  ->orWhere(function($sub) {
                      $sub->where('state', 'show')
                          ->where('stock', '>', 0);
                  });
            });

        $product = is_numeric($id)
        ? (clone $query)->where('id', $id)->first()
        : (clone $query)->where('uuid', $id)->first();

        if (! $product) {
            return response()->json([
                'status' => false,
                'message'=> 'Product not found',
            ], 404);
        }

        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $productUrl = 'https://portal.mybridgeinternational.org/mbi-portal-files/public/';
        $donatedUrl = 'https://admin.mybridgeinternational.org/mbi-admin-files/public/';
        $data = [
            'id'          => $product->id,
            'uuid'        => $product->uuid,
            'name'        => $product->name,
            'description' => $product->description,
            'category'    => $product->category,
            'price'       => $product->price,
            'stock'       => $product->stock,
            'state'       => $product->state,
            'status'      => $product->status,
            'warranty'    => $product->warranty,
            'product_type'=> $product->product_type,
            'created_by'  => $product->created_by,
            'images'      => $product->images->map(fn ($img) => [
                'id'        => $img->id,
                'image_url' => $product->product_type === 'donation' 
                    ? rtrim($donatedUrl, '/') . '/' . ltrim($img->image_url, '/') 
                    : (Storage::disk('public')->exists($img->image_url) 
                        ? rtrim($baseUrl, '/') . '/' . ltrim($img->image_url, '/') 
                        : rtrim($productUrl, '/') . '/' . ltrim($img->image_url, '/')),
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

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Ensure only verified sellers can add products
        if (!$user->isSeller || !$user->kyc_verified_at ) {
            return response()->json([
                'success' => false,
                'message' => 'Only verified sellers can add products'
            ], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'status'      => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],

            // Optional fields
            'discount'                   => ['nullable', 'string', 'max:255'],
            'warranty'                   => ['nullable', 'string', 'max:255'],
            'link'                       => ['nullable', 'string', 'max:255'],
            'manufacturer'               => ['nullable', 'string', 'max:255'],
            'ukca_mark'                  => ['nullable', 'string', 'max:255'],
            'model_number'               => ['nullable', 'string', 'max:255'],
            'condition'                  => ['nullable', 'string', 'max:255'],
            'age_of_equipment'           => ['nullable', 'string', 'max:255'],
            'last_serviced_date'         => ['nullable', 'date'],
            'known_issues'               => ['nullable', 'boolean'],
            'known_issues_details'       => ['nullable', 'string'],
            'accessories'                => ['nullable', 'string'],
            'pickup_available_date'      => ['nullable', 'date'],
            'equipment_location'         => ['nullable', 'string', 'max:255'],
            'shipping_cost_contribution' => ['nullable', 'string', 'max:255'],

            // Images
            'images'   => ['nullable', 'array'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {
            // Create product
            $product = new Product();
            $product->fill($validated);
            $product->product_type = 'product';
            $product->created_by   = $user->id;
            $product->save();

            // Handle images safely (single or multiple)
            if ($request->hasFile('images')) {
                $files = $request->file('images');

                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $idx => $file) {
                    $path = $file->store('product_images', 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url'  => $path,
                        'sort_order' => $idx,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data'    => $product
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Product creation failed',
                'error'   => $e->getMessage()
            ], 500);
        }
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

        $product = Product::where('uuid', $id)->first();
        if (! $product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        if (!$user->isSeller || !$user->kyc_verified_at || $product->created_by !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'        => ['sometimes', 'string', 'max:255'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'stock'       => ['sometimes', 'integer', 'min:0'],
            'status'      => ['sometimes', 'string', 'max:255'],
            'category'    => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],

            'warranty'                   => ['nullable', 'string', 'max:255'],
            'discount'                   => ['nullable', 'string', 'max:255'],
            'link'                       => ['nullable', 'string', 'max:255'],
            'manufacturer'               => ['nullable', 'string', 'max:255'],
            'ukca_mark'                  => ['nullable', 'string', 'max:255'],
            'model_number'               => ['nullable', 'string', 'max:255'],
            'condition'                  => ['nullable', 'string', 'max:255'],
            'age_of_equipment'           => ['nullable', 'string', 'max:255'],
            'last_serviced_date'         => ['nullable', 'date'],
            'known_issues'               => ['nullable', 'boolean'],
            'known_issues_details'       => ['nullable', 'string'],
            'accessories'                => ['nullable', 'string'],
            'pickup_available_date'      => ['nullable', 'date'],
            'equipment_location'         => ['nullable', 'string', 'max:255'],
            'shipping_cost_contribution' => ['nullable', 'string', 'max:255'],

            // Images
            'images'   => ['nullable'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,gif,webp'],

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $productData = collect($validated)->except(['images'])->toArray();
        $product->fill($productData);
        $product->save();

        if ($request->hasFile('images')) {
            $files = $request->file('images');

            if (! is_array($files)) {
                $files = [$files];
            }

            $currentCount = ProductImage::where('product_id', $product->id)->count();

            foreach ($files as $idx => $file) {
                $path = $file->store('product_images', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url'  => $path,
                    'sort_order' => $currentCount + $idx,
                ]);
            }
        }

        // return $this->show($product->id);
        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $productUrl = 'https://portal.mybridgeinternational.org/mbi-portal-files/public/';
        $donatedUrl = 'https://admin.mybridgeinternational.org/mbi-admin-files/public/';

        $product->load('images');

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully',
            'data' => [
                'id'          => $product->id,
                'uuid'        => $product->uuid,
                'name'        => $product->name,
                'description' => $product->description,
                'category'    => $product->category,
                'price'       => $product->price,
                'stock'       => $product->stock,
                'state'       => $product->state,
                'status'      => $product->status,
                'warranty'    => $product->warranty,
                'product_type'=> $product->product_type,
                'created_by'  => $product->created_by,
                'images'      => $product->images->map(fn ($img) => [
                    'id'        => $img->id,
                    'image_url' => $product->product_type === 'donation'
                        ? rtrim($donatedUrl, '/') . '/' . ltrim($img->image_url, '/')
                        : (Storage::disk('public')->exists($img->image_url)
                            ? rtrim($baseUrl, '/') . '/' . ltrim($img->image_url, '/')
                            : rtrim($productUrl, '/') . '/' . ltrim($img->image_url, '/')),
                    'sort_order'=> $img->sort_order,
                ]),
            ],
        ]);

    }
    /**
     * get all product for a seller owner only.
     */

    public function userProducts($userId)
    {
        $user = Auth::user();

        // 1. Unauthorized
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // 2. Forbidden (seller + KYC + ownership)
        if (! $user->isSeller || ! $user->kyc_verified_at || $user->id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden'
            ], 403);
        }

        try {
            $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
            $productUrl = 'https://portal.mybridgeinternational.org/mbi-portal-files/public/';


            $products = Product::with('images')
                ->where('created_by', $user->id) // enforce ownership
                ->where('product_type', 'product')
                ->orderByDesc('id')
                ->paginate(10)
                ->through(function ($product) use ($baseUrl, $productUrl) {

                    return [
                        'id' => $product->id,
                        'uuid' => $product->uuid,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'stock' => $product->stock,
                        'state' => $product->state,
                        'status' => $product->status,
                        'category' => $product->category,
                        'created_at' => $product->created_at?->toDateTimeString(),
                        // images
                        'images' => $product->images->map(function ($img) use ($baseUrl, $productUrl) {
                            return [
                                'id'  => $img->id,
                                'image_url' => $img->image_url
                                    ? (Storage::disk('public')->exists($img->image_url)
                                        ? rtrim($baseUrl, '/') . '/' . ltrim($img->image_url, '/')
                                        : rtrim($productUrl, '/') . '/' . ltrim($img->image_url, '/'))
                                    : null,
                            ];
                        }),
                        'image_count' => $product->images->count(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data'    => $products
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    /**
     * draft product for a seller owner only.
     */
    public function draftProduct($uuid)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $product = Product::where('uuid', $uuid)->first();

        if (! $product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        if (! $user->isSeller || ! $user->kyc_verified_at || $product->created_by !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        if ($product->product_type !== 'product') {
            return response()->json(['status' => false, 'message' => 'Only products can be drafted'], 422);
        }

        if ($product->state === 'draft') {
            return response()->json(['status' => true, 'message' => 'Product already drafted']);
        }

        $product->state = 'draft';
        $product->save();

        return response()->json([
            'status' => true,
            'message' => 'Product moved to draft',
            'data' => [
                'uuid' => $product->uuid,
                'state' => $product->state,
            ],
        ]);
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
        if (!$user->isSeller || !$user->kyc_verified_at || $product->created_by !== $user->id) {
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
        if (! $product || $product->created_by !== $user->id || !$user->isSeller || !$user->kyc_verified_at) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        if ($image->image_url && Storage::disk('public')->exists($image->image_url)) {
            Storage::disk('public')->delete($image->image_url);
        }
        $image->delete();

        return response()->json(['status' => true, 'message' => 'Image deleted']);
    }

    /**
     * Submit a bid for a donation product.
     */
    public function submitBid(Request $request, $productId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $product = Product::where('uuid',$productId)->first();
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        if (strtolower($product->product_type ?? '') !== 'donation') {
            return response()->json(['status' => false, 'message' => 'Not a donation product'], 404);
        }

        $validator = Validator::make($request->all(), [
            'applicant_type' => ['required', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_website' => ['nullable', 'string', 'max:255'],
            'facility_address' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            // 'equipment_name' => ['required', 'string', 'max:255'],
            'urgency' => ['required', 'string', 'max:255'],
            'preferred_manufacturer' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'can_contribute' => ['required', 'string', 'max:255'],
            'budget' => ['nullable', 'string', 'max:255'],
            'statement_of_need' => ['required', 'string'],
            'intended_use' => ['required', 'string'],
            'agreed' => ['required','boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $code = 'REQ-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $bid = new ProductBidding();
        $bid->product_id = $product->id;
        $bid->user_id = $user->id;
        $bid->request_code = $code;
        $bid->applicant_type = $data['applicant_type'];
        $bid->organization_name = $data['organization_name'] ?? null;
        $bid->organization_website = $data['organization_website'] ?? null;
        $bid->facility_address = $data['facility_address'];
        $bid->email = $data['email'];
        $bid->phone = $data['phone'];
        $bid->contact_person = $data['contact_person'];
        $bid->equipment_name = $product->name;
        $bid->urgency = $data['urgency'];
        $bid->preferred_manufacturer = $data['preferred_manufacturer'] ?? null;
        $bid->quantity = $data['quantity'];
        $bid->can_contribute = $data['can_contribute'];
        $bid->budget = $data['budget'] ?? null;
        $bid->statement_of_need = $data['statement_of_need'];
        $bid->intended_use = $data['intended_use'];
        $bid->agreed = true;
        $bid->status = 'pending';
        $bid->save();

        return response()->json([
            'status' => true,
            'message' => 'Bid submitted successfully',
            'data' => [
                'bid_request_code' => $code,
                'bid' => $bid
            ]
        ], 201);
    }

    /**
     * Get user's biddings.
     */
    public function myBiddings()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $biddings = ProductBidding::with('product')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $biddings
        ]);
    }

    /**
     * Show a specific bid.
     */
    public function showBid($bidId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $bid = ProductBidding::find($bidId);

        if (!$bid) {
             return response()->json(['status' => false, 'message' => 'Bid not found'], 404);
        }

        if ((int) $bid->user_id !== (int) $user->id) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $bid->load('product');

        return response()->json([
            'status' => true,
            'data' => $bid
        ]);
    }
}
