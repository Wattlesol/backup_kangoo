<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FrontendSetting;
use App\Http\Resources\API\LandingPageSettingResource;
use App\Models\PaymentGateway;
use App\Http\Resources\API\PaymentGatewayResource;
use Illuminate\Support\Facades\Cache;

class FrontendSettingController extends Controller
{
    public function getLandingPageSetting(Request $request){
        $ttl = 300; // 5 minutes
        $key = 'api:landing-page-list:' . http_build_query([
            'per_page' => $request->get('per_page'),
            'page' => $request->get('page'),
            'status' => 1,
        ]);

        $payload = Cache::remember($key, $ttl, function () use ($request) {
            $landingPage = FrontendSetting::where('status',1);

            $per_page = config('constant.PER_PAGE_LIMIT');
            if( $request->has('per_page') && !empty($request->per_page)){
                if(is_numeric($request->per_page)){
                    $per_page = $request->per_page;
                }
                if($request->per_page === 'all' ){
                    $per_page = $landingPage->count();
                }
            }
            $landingPage = $landingPage->paginate($per_page);
            $items = LandingPageSettingResource::collection($landingPage);
            $itemsArray = $items->resolve();

            return [
                'pagination' => [
                    'total_items' => $landingPage->total(),
                    'per_page' => $landingPage->perPage(),
                    'currentPage' => $landingPage->currentPage(),
                    'totalPages' => $landingPage->lastPage(),
                    'from' => $landingPage->firstItem(),
                    'to' => $landingPage->lastItem(),
                    'next_page' => $landingPage->nextPageUrl(),
                    'previous_page' => $landingPage->previousPageUrl(),
                ],
                'data' => $itemsArray,
            ];
        });

        $etag = 'W/"'.md5(json_encode($payload)).'"';
        $resp = comman_custom_response($payload);
        if (in_array($etag, $request->getETags(), true)) {
            return response()->json([], 304)->header('ETag', $etag)->header('Cache-Control', 'public, max-age='.$ttl);
        }
        return $resp->header('ETag', $etag)->header('Cache-Control', 'public, max-age='.$ttl);
    }

    public function getPaymentGatewayList(Request $request){
        $paymentstatus = PaymentGateway::where('status',1);

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $paymentstatus->count();
            }
        }
        $paymentstatus = $paymentstatus->paginate($per_page);
        $items = PaymentGatewayResource::collection($paymentstatus);

        $response = [
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

        return comman_custom_response($response);
    }


}
