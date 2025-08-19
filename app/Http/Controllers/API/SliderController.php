<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Slider;
use App\Http\Resources\API\SliderResource;
use Illuminate\Support\Facades\Cache;

class SliderController extends Controller
{
    public function getSliderList(Request $request){
        $ttl = 300; // 5 minutes
        $key = 'api:slider-list:' . http_build_query([
            'per_page' => $request->get('per_page'),
            'page' => $request->get('page'),
            'status' => 1,
        ]);

        $payload = Cache::remember($key, $ttl, function () use ($request) {
            $slider = Slider::where('status',1)->with(['service','media']);

            $per_page = config('constant.PER_PAGE_LIMIT');
            if( $request->has('per_page') && !empty($request->per_page)){
                if(is_numeric($request->per_page)){
                    $per_page = $request->per_page;
                }
                if($request->per_page === 'all' ){
                    $per_page = $slider->count();
                }
            }

            $slider = $slider->orderBy('created_at','desc')->paginate($per_page);
            $items = SliderResource::collection($slider);

            return [
                'pagination' => [
                    'total_items' => $items->total(),
                    'per_page' => $items->perPage(),
                    'currentPage' => $items->currentPage(),
                    'totalPages' => $items->lastPage(),
                    'from' => $items->firstItem(),
                    'to' => $items->lastItem(),
                    'next_page' => $items->nextPageUrl(),
                    'previous_page' => $items->previousPageUrl(),
                ],
                'data' => $items,
            ];
        });

        $etag = 'W/"'.md5(json_encode($payload)).'"';
        $resp = comman_custom_response($payload);
        if (in_array($etag, $request->getETags(), true)) {
            return response()->json([], 304)->header('ETag', $etag)->header('Cache-Control', 'public, max-age='.$ttl);
        }
        return $resp->header('ETag', $etag)->header('Cache-Control', 'public, max-age='.$ttl);
    }
}