<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class MarketplaceController extends Controller
{
    /**
     * Update seller’s documents and related details.
     */
    public function updateSellerDocs(Request $request)
    {
        // Ensure we have a concrete Eloquent User model instance
        $userId = Auth::id();
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // if ($user->category !== 'seller') {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Only sellers can update seller documents.',
        //     ], 403);
        // }

        $validator = Validator::make($request->all(), [
            'corporate_registration_papers' => ['nullable', 'file'],
            'product_approval'              => ['nullable', 'file'],
            'warranty_policy_document'      => ['nullable', 'file'],
            'export_capability_statement'   => ['nullable', 'file'],
            'incoterms_preference'          => ['nullable', 'string'],
            'admin_comment'                 => ['nullable', 'string'],
            'rejection_comment'             => ['nullable', 'string'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Helper to store a file into public disk and update model field
        $storeFile = function (string $field) use ($request, $user) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                // delete old if exists and is a storage path
                if (!empty($user->{$field}) && Storage::disk('public')->exists($user->{$field})) {
                    Storage::disk('public')->delete($user->{$field});
                }
                $path = $file->store('seller_docs', 'public');
                $user->{$field} = $path;
            }
        };

        $storeFile('corporate_registration_papers');
        $storeFile('product_approval');
        $storeFile('warranty_policy_document');
        $storeFile('export_capability_statement');

        // Non-file fields
        if ($request->filled('incoterms_preference')) {
            $user->incoterms_preference = $request->input('incoterms_preference');
        }
        if ($request->filled('admin_comment')) {
            $user->admin_comment = $request->input('admin_comment');
        }
        if ($request->filled('rejection_comment')) {
            $user->rejection_comment = $request->input('rejection_comment');
        }


        $user->save();

        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';

        $payload = [
            'uuid'                        => $user->uuid,
            'full_name'                  => $user->full_name,
            'email'                      => $user->email,
            'incoterms_preference'       => $user->incoterms_preference,
            'admin_comment'              => $user->admin_comment,
            'rejection_comment'          => $user->rejection_comment,
            'corporate_registration_papers' => $user->corporate_registration_papers ? $baseUrl . $user->corporate_registration_papers : null,
            'product_approval'              => $user->product_approval ? $baseUrl . $user->product_approval : null,
            'warranty_policy_document'      => $user->warranty_policy_document ? $baseUrl . $user->warranty_policy_document : null,
            'export_capability_statement'   => $user->export_capability_statement ? $baseUrl . $user->export_capability_statement : null,
        ];

        return response()->json([
            'status'  => true,
            'message' => 'Seller documents updated successfully.',
            'data'    => $payload,
        ], 200);
    }

   public function verifyKyc(Request $request)
    {
        try {

            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'institution'   => ['required', 'string', 'max:255'],
                'phone'    => ['required', 'string', 'max:255'],
                'country'         => ['required', 'string', 'max:255'],
                'license_number' => ['required', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            $exists = User::where('uuid', $user->uuid)
                ->where('institution', $data['institution'])
                ->where('phone', $data['phone'])
                // ->where('country', $data['country'])
                ->where('license_number', $data['license_number'])
                ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submitted KYC data does not match user records'
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'KYC data verified successfully'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
