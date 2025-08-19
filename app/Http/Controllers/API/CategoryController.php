<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Resources\API\CategoryResource;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function getCategoryList(Request $request){
        // Build a cache key from query params and auth role (safe, short ttl)
        $auth = auth()->user();
        $role = $auth && method_exists($auth, 'hasRole') && $auth->hasRole('admin') ? 'admin' : 'guest';
        $key = 'api:category-list:' . http_build_query([
            'is_featured' => $request->get('is_featured'),
            'per_page' => $request->get('per_page'),
            'page' => $request->get('page'),
            'role' => $role,
        ]);

        $ttl = 60; // seconds

        $payload = Cache::remember($key, $ttl, function () use ($request, $role) {
            $query = Category::query()
                ->when($role !== 'admin', fn($q) => $q->where('status', 1))
                ->withCount('services')
                ->with('media'); // avoid N+1 for images

            if($request->has('is_featured')){
                $query->where('is_featured', $request->is_featured);
            }

            $per_page = config('constant.PER_PAGE_LIMIT');
            if( $request->has('per_page') && !empty($request->per_page)){
                if(is_numeric($request->per_page)){
                    $per_page = $request->per_page;
                }
                if($request->per_page === 'all' ){
                    $per_page = $query->count();
                }
            }

            // Optional filter: fetch only specific IDs (comma-separated)
            if ($request->has('ids') && !empty($request->ids)) {
                $ids = array_filter(explode(',', $request->ids));
                if (!empty($ids)) {
                    $query->whereIn('id', $ids);
                }
            }

            $paginated = $query->select(['id','name','description','status','is_featured','color','updated_at','deleted_at'])
                ->orderBy('name','asc')->paginate($per_page);
            $items = CategoryResource::collection($paginated);
            $itemsArray = $items->resolve(); // convert to plain array for safe caching/etag

            return [
                'pagination' => [
                    'total_items' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'currentPage' => $paginated->currentPage(),
                    'totalPages' => $paginated->lastPage(),
                    'from' => $paginated->firstItem(),
                    'to' => $paginated->lastItem(),
                    'next_page' => $paginated->nextPageUrl(),
                    'previous_page' => $paginated->previousPageUrl(),
                ],
                'data' => $itemsArray,
            ];
        });

        // HTTP caching headers (client reuse across reloads when not disabled)
        $etag = 'W/"'.md5(json_encode($payload)).'"';
        $resp = comman_custom_response($payload);
        if (request()->headers->get('If-None-Match') === $etag) {
            return response()->json([], 304)->header('ETag', $etag)->header('Cache-Control', 'public, max-age='.$ttl);
        }
        return $resp->header('ETag', $etag)->header('Cache-Control', 'public, max-age='.$ttl);
    }
}
