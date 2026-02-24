<?php

namespace App\Http\Controllers;

use App\Models\Journals;
use App\Models\Articles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ResourcesController extends Controller
{
    public function index(Request $request)
    {
        $query = Journals::query()->orderByDesc('created_at');

        if ($accessType = $request->query('access_type')) {
            $query->where('access_type', $accessType);
        }

        if (!is_null($request->query('year'))) {
            $query->where('publication_year', (int) $request->query('year'));
        }

        if ($scope = $request->query('scope')) {
            if ($scope === 'mine') {
                $user = Auth::guard('sanctum')->user();
                if ($user) {
                    $query->where('user_id', $user->id);
                } else {
                    $query->whereNull('user_id');
                }
            }
        }

        if ($search = $request->query('q')) {
            $query->where(function ($w) use ($search) {
                $w->where('title', 'like', '%' . $search . '%')
                    ->orWhere('authors', 'like', '%' . $search . '%')
                    ->orWhere('abstract', 'like', '%' . $search . '%');
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $journals = $query->paginate($perPage);

        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';

        $journals->getCollection()->transform(function (Journals $journal) use ($baseUrl) {
            return [
                'id' => $journal->id,
                'title' => $journal->title,
                'slug' => $journal->slug,
                'status' => $journal->status,
                'publication_year' => $journal->publication_year,
                'authors' => $journal->authors,
                'access_type' => $journal->access_type,
                'is_restricted' => $journal->is_restricted,
                'abstract' => $journal->abstract,
                'introduction' => $journal->introduction,
                'publication_url' => $journal->publication_url,
                'document_url' => $journal->document_path ? $baseUrl . $journal->document_path : null,
                'tags' => $journal->tags,
                'created_at' => $journal->created_at,
                'updated_at' => $journal->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Journals fetched successfully',
            'data' => $journals,
        ]);
    }

    public function articlesIndex(Request $request)
    {
        $query = Articles::query()->orderByDesc('created_at');

        if ($accessType = $request->query('access_type')) {
            $query->where('access_type', $accessType);
        }

        if (!is_null($request->query('year'))) {
            $query->where('publication_year', (int) $request->query('year'));
        }

        if ($scope = $request->query('scope')) {
            if ($scope === 'mine') {
                $user = Auth::guard('sanctum')->user();
                if ($user) {
                    $query->where('user_id', $user->id);
                } else {
                    $query->whereNull('user_id');
                }
            }
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($w) use ($search) {
                $w->where('title', 'like', '%' . $search . '%')
                    ->orWhere('authors', 'like', '%' . $search . '%')
                    ->orWhere('abstract', 'like', '%' . $search . '%');
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $articles = $query->paginate($perPage);

        $articles->getCollection()->transform(function (Articles $article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'status' => $article->status,
                'publication_year' => $article->publication_year,
                'authors' => $article->authors,
                'access_type' => $article->access_type,
                'is_restricted' => $article->is_restricted,
                'abstract' => $article->abstract,
                'introduction' => $article->introduction,
                'body' => $article->body,
                'image_url' => $article->image_url,
                'no_of_likes' => $article->no_of_likes,
                'no_of_comments' => $article->no_of_comments,
                'publication_url' => $article->publication_url,
                'tags' => $article->tags,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Articles fetched successfully',
            'data' => $articles,
        ]);
    }

    public function show($id)
    {
        $journal = $this->findJournal($id);

        if (!$journal) {
            return response()->json([
                'success' => false,
                'message' => 'Journal not found',
            ], 404);
        }

        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';

        $payload = [
            'id' => $journal->id,
            'title' => $journal->title,
            'slug' => $journal->slug,
            'status' => $journal->status,
            'publication_year' => $journal->publication_year,
            'authors' => $journal->authors,
            'access_type' => $journal->access_type,
            'is_restricted' => $journal->is_restricted,
            'abstract' => $journal->abstract,
            'introduction' => $journal->introduction,
            'publication_url' => $journal->publication_url,
            'document_url' => $journal->document_path ? $baseUrl . $journal->document_path : null,
            'tags' => $journal->tags,
            'created_at' => $journal->created_at,
            'updated_at' => $journal->updated_at,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Journal fetched successfully',
            'data' => $payload,
        ]);
    }

    public function articlesShow($id)
    {
        $article = $this->findArticle($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        $payload = [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'status' => $article->status,
            'publication_year' => $article->publication_year,
            'authors' => $article->authors,
            'access_type' => $article->access_type,
            'is_restricted' => $article->is_restricted,
            'abstract' => $article->abstract,
            'introduction' => $article->introduction,
            'body' => $article->body,
            'image_url' => $article->image_url,
            'no_of_likes' => $article->no_of_likes,
            'no_of_comments' => $article->no_of_comments,
            'publication_url' => $article->publication_url,
            'tags' => $article->tags,
            'created_at' => $article->created_at,
            'updated_at' => $article->updated_at,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Article fetched successfully',
            'data' => $payload,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['nullable', 'string', 'max:255'],
            'access_type' => ['required', 'in:general,peer_reviewed'],
            'is_restricted' => ['nullable', 'boolean'],
            'abstract' => ['nullable', 'string'],
            'introduction' => ['nullable', 'string'],
            'publication_url' => ['nullable', 'url', 'required_without:document'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:5120', 'required_without:publication_url'],
            'tags' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $slug = $this->generateUniqueSlug($data['title']);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $documentPath = $file->store('journals', 'public');
        }

        $journal = Journals::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'slug' => $slug,
            'status' => 'pending',
            'publication_year' => $data['publication_year'] ?? null,
            'authors' => $data['authors'] ?? null,
            'access_type' => $data['access_type'],
            'is_restricted' => $data['is_restricted'] ?? false,
            'abstract' => $data['abstract'] ?? null,
            'introduction' => $data['introduction'] ?? null,
            'publication_url' => $data['publication_url'] ?? null,
            'document_path' => $documentPath,
            'tags' => $data['tags'] ?? null,
        ]);

        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $journal->document_path = $baseUrl . $journal->document_path;

        return response()->json([
            'success' => true,
            'message' => 'Journal created successfully',
            'data' => $journal,
        ], 201);
    }

    public function articlesStore(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }


        $validator = Validator::make($request->all(), [
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['nullable', 'string', 'max:255'],
            'access_type' => ['required', 'in:general,peer_reviewed'],
            'is_restricted' => ['nullable', 'boolean'],
            'abstract' => ['nullable', 'string'],
            'introduction' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'image_url' => ['nullable', 'url'],
            // 'publication_url' => ['required', 'url'],
            'tags' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imagePath = $file->store('articles', 'public');
            $data['image_url'] = asset('storage/' . $imagePath);
        }

        $slug = $this->generateUniqueSlugForArticles($data['title']);

        $article = Articles::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'slug' => $slug,
            'status' => 'pending',
            'publication_year' => $data['publication_year'] ?? null,
            'authors' => $data['authors'] ?? null,
            'access_type' => $data['access_type'],
            'is_restricted' => $data['is_restricted'] ?? false,
            'abstract' => $data['abstract'] ?? null,
            'introduction' => $data['introduction'] ?? null,
            'body' => $data['body'],
            'image_url' => $data['image_url'] ?? null,
            'no_of_likes' => 0,
            'no_of_comments' => 0,
            'publication_url' => $data['publication_url'] ?? '',
            'tags' => $data['tags'] ?? null,
        ]);

        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $article->image_url = $baseUrl . $article->image_url;

        return response()->json([
            'success' => true,
            'message' => 'Article created successfully',
            'data' => $article,
        ], 201);
    }

    public function update($id, Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $journal = $this->findJournal($id);

        if (!$journal) {
            return response()->json([
                'success' => false,
                'message' => 'Journal not found',
            ], 404);
        }

        if ($journal->user_id && $journal->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'title' => ['nullable', 'string', 'max:255'],
            'authors' => ['nullable', 'string', 'max:255'],
            'access_type' => ['nullable', 'in:general,peer_reviewed'],
            'is_restricted' => ['nullable', 'boolean'],
            'abstract' => ['nullable', 'string'],
            'introduction' => ['nullable', 'string'],
            'publication_url' => ['nullable', 'url'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:5120'],
            'tags' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (!empty($data['title']) && $data['title'] !== $journal->title) {
            $journal->slug = $this->generateUniqueSlug($data['title']);
        }

        if ($request->hasFile('document')) {
            if (!empty($journal->document_path) && Storage::disk('public')->exists($journal->document_path)) {
                Storage::disk('public')->delete($journal->document_path);
            }
            $file = $request->file('document');
            $journal->document_path = $file->store('journals', 'public');
        }

        $journal->fill([
            'title' => $data['title'] ?? $journal->title,
            'status' => $journal->status,
            'publication_year' => $data['publication_year'] ?? $journal->publication_year,
            'authors' => $data['authors'] ?? $journal->authors,
            'access_type' => $data['access_type'] ?? $journal->access_type,
            'is_restricted' => array_key_exists('is_restricted', $data) ? $data['is_restricted'] : $journal->is_restricted,
            'abstract' => $data['abstract'] ?? $journal->abstract,
            'introduction' => $data['introduction'] ?? $journal->introduction,
            'publication_url' => $data['publication_url'] ?? $journal->publication_url,
            'tags' => $data['tags'] ?? $journal->tags,
        ]);

        $journal->save();

        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $journal->document_path = $baseUrl . $journal->document_path;

        return response()->json([
            'success' => true,
            'message' => 'Journal updated successfully',
            'data' => $journal,
        ]);
    }

    public function articlesUpdate($id, Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $article = $this->findArticle($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        if ($article->user_id && $article->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'title' => ['nullable', 'string', 'max:255'],
            'authors' => ['nullable', 'string', 'max:255'],
            'access_type' => ['nullable', 'in:general,peer_reviewed'],
            'is_restricted' => ['nullable', 'boolean'],
            'abstract' => ['nullable', 'string'],
            'introduction' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'image' => ['nullable'],
            'publication_url' => ['nullable', 'url'],
            'tags' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (!empty($data['title']) && $data['title'] !== $article->title) {
            $article->slug = $this->generateUniqueSlugForArticles($data['title']);
        }

        // return $request->all();
        if ($request->has('image')) {
            if (!empty($article->image_url) && Storage::disk('public')->exists($article->image_url)) {
                Storage::disk('public')->delete($article->image_url);
            }
            $file = $request->file('image');
            // return $file;
            $article->image_url = $file->store('articles', 'public');
        }

        $article->fill([
            'title' => $data['title'] ?? $article->title,
            'publication_year' => $data['publication_year'] ?? $article->publication_year,
            'authors' => $data['authors'] ?? $article->authors,
            'access_type' => $data['access_type'] ?? $article->access_type,
            'is_restricted' => array_key_exists('is_restricted', $data) ? $data['is_restricted'] : $article->is_restricted,
            'abstract' => $data['abstract'] ?? $article->abstract,
            'introduction' => $data['introduction'] ?? $article->introduction,
            'body' => $data['body'] ?? $article->body,
            'image_url' => $article->image_url ?? $data['image_url'],
            'publication_url' => $data['publication_url'] ?? $article->publication_url,
            'tags' => $data['tags'] ?? $article->tags,
        ]);

        $article->save();

        $baseUrl = 'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/';
        $article->image_url = $baseUrl . $article->image_url;

        return response()->json([
            'success' => true,
            'message' => 'Article updated successfully',
            'data' => $article,
        ]);
    }

    public function destroy($id, Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $journal = $this->findJournal($id);

        if (!$journal) {
            return response()->json([
                'success' => false,
                'message' => 'Journal not found',
            ], 404);
        }

        if ($journal->user_id && $journal->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        if (!empty($journal->document_path) && Storage::disk('public')->exists($journal->document_path)) {
            Storage::disk('public')->delete($journal->document_path);
        }

        $journalId = $journal->id;
        $journal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Journal deleted successfully',
            'journalId' => (string) $journalId,
            'status' => 'deleted',
        ]);
    }

    public function articlesDestroy($id, Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $article = $this->findArticle($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        if ($article->user_id && $article->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $articleId = $article->id;
        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully',
            'articleId' => (string) $articleId,
            'status' => 'deleted',
        ]);
    }

    private function findJournal($id): ?Journals
    {
        if (is_numeric($id)) {
            return Journals::find($id);
        }

        return Journals::where('slug', $id)->first();
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (Journals::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function findArticle($id): ?Articles
    {
        if (is_numeric($id)) {
            return Articles::find($id);
        }

        return Articles::where('slug', $id)->first();
    }

    private function generateUniqueSlugForArticles(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (Articles::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
