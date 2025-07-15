<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use App\Http\Controllers\API;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/*
normal api_token
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
require __DIR__.'/admin-api.php';

Route::get('category-list',[API\CategoryController::class,'getCategoryList']);
Route::get('subcategory-list',[API\SubCategoryController::class,'getSubCategoryList']);
Route::get('service-list',[API\ServiceController::class,'getServiceList']);
Route::get('type-list',[API\CommanController::class,'getTypeList']);
Route::get('blog-list',[API\BlogController::class,'getBlogList']);
Route::post('blog-detail',[API\BlogController::class,'getBlogDetail']);
Route::get('landing-page-list',[API\FrontendSettingController::class,'getLandingPageSetting']);

Route::post('country-list',[ API\CommanController::class, 'getCountryList' ]);
Route::post('state-list',[ API\CommanController::class, 'getStateList' ]);
Route::post('city-list',[ API\CommanController::class, 'getCityList' ]);
Route::get('search-list', [ API\CommanController::class, 'getSearchList' ] );
Route::get('slider-list',[ API\SliderController::class, 'getSliderList' ]);
Route::get('top-rated-service',[ API\ServiceController::class, 'getTopRatedService' ]);
Route::get('coupon-list',[ API\CouponController::class, 'getCouponList' ]);
Route::post('configurations', [ API\DashboardController::class, "configurations"]);

// E-commerce Public Routes
Route::get('product-categories', [API\ProductController::class, 'categories']);
Route::get('products', [API\ProductController::class, 'index']);
Route::get('products/{id}', [API\ProductController::class, 'show']);
Route::get('products-search', [API\ProductController::class, 'search']);
Route::get('featured-products', [API\ProductController::class, 'featured']);
Route::get('stores', [API\StoreController::class, 'index']);
Route::get('stores/{id}', [API\StoreController::class, 'show']);
Route::get('stores/{id}/products', [API\StoreController::class, 'products']);
Route::get('nearby-stores', [API\StoreController::class, 'nearby']);



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register',[API\User\UserController::class, 'register']);
Route::post('login',[API\User\UserController::class,'login']);
Route::post('forgot-password',[ API\User\UserController::class,'forgotPassword']);
Route::post('social-login',[ API\User\UserController::class, 'socialLogin' ]);
Route::post('contact-us', [ API\User\UserController::class, 'contactUs' ] );
Route::post('user-email-verify',[API\User\UserController::class,'verify']);



Route::get('dashboard-detail',[ API\DashboardController::class, 'dashboardDetail' ]);
Route::get('service-rating-list',[API\ServiceController::class,'getServiceRating']);
Route::get('user-detail',[API\User\UserController::class, 'userDetail']);
Route::post('service-detail', [ API\ServiceController::class, 'getServiceDetail' ] );
Route::get('user-list',[API\User\UserController::class, 'userList']);
Route::get('booking-status', [ API\BookingController::class, 'bookingStatus' ] );
Route::post('handyman-reviews',[API\User\UserController::class, 'handymanReviewsList']);
Route::post('service-reviews', [ API\ServiceController::class, 'serviceReviewsList' ] );
Route::get('post-job-status', [ API\PostJobRequestController::class, 'postRequestStatus' ] );

// Route::get('booking-list', [ API\BookingController::class, 'getBookingList' ] );

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('service-save', [ App\Http\Controllers\ServiceController::class, 'store' ] );
    //Route::post('service-save', [ App\Http\Controllers\ServiceController::class, 'store' ] );
    Route::post('service-delete/{id}', [ App\Http\Controllers\ServiceController::class, 'destroy' ] );
    Route::post('booking-save', [ App\Http\Controllers\BookingController::class, 'store' ] );
    Route::post('get-payment-method', [ App\Http\Controllers\BookingController::class, 'getPaymentMethod' ] );
    Route::post('create-stripe-payment', [ App\Http\Controllers\BookingController::class, 'createStripePayment' ] );


    Route::post('booking-update', [ API\BookingController::class, 'bookingUpdate' ] );
    Route::get('provider-dashboard',[ API\DashboardController::class, 'providerDashboard' ]);
    Route::get('admin-dashboard',[ API\DashboardController::class, 'adminDashboard' ]);
    Route::get('booking-list', [ API\BookingController::class, 'getBookingList' ] );
    Route::post('booking-detail', [ API\BookingController::class, 'getBookingDetail' ] );
    Route::post('save-booking-rating', [ API\BookingController::class, 'saveBookingRating' ] );
    Route::post('delete-booking-rating', [ API\BookingController::class, 'deleteBookingRating' ] );
    Route::get('get-user-ratings', [ API\BookingController::class, 'getUserRatings' ] );

    Route::post('save-favourite',[ API\ServiceController::class, 'saveFavouriteService' ]);
    Route::post('delete-favourite',[ API\ServiceController::class, 'deleteFavouriteService' ]);
    Route::get('user-favourite-service',[ API\ServiceController::class, 'getUserFavouriteService' ]);

    Route::post('booking-action',[ API\BookingController::class, 'action' ] );

    Route::post('booking-assigned',[ App\Http\Controllers\BookingController::class,'bookingAssigned'] );

    Route::post('user-update-status',[API\User\UserController::class, 'userStatusUpdate']);
    Route::post('change-password',[API\User\UserController::class, 'changePassword']);
    Route::post('update-profile',[API\User\UserController::class,'updateProfile']);
    Route::post('notification-list',[API\NotificationController::class,'notificationList']);
    Route::post('remove-file', [ App\Http\Controllers\HomeController::class, 'removeFile' ] );
    Route::get('logout',[ API\User\UserController::class, 'logout' ]);
    Route::post('save-payment',[API\PaymentController::class, 'savePayment']);

    Route::get('payment-list',[API\PaymentController::class, 'paymentList']);
    Route::post('transfer-payment',[API\PaymentController::class, 'transferPayment']);
    Route::get('payment-history',[API\PaymentController::class, 'paymentHistory']);
    Route::get('cash-detail',[API\PaymentController::class, 'paymentDetail']);
    Route::get('user-bank-detail',[API\CommanController::class, 'getBankList']);


    Route::post('save-provideraddress', [ App\Http\Controllers\ProviderAddressMappingController::class, 'store' ]);
    Route::get('provideraddress-list', [ API\ProviderAddressMappingController::class, 'getProviderAddressList' ]);
    Route::post('provideraddress-delete/{id}', [ App\Http\Controllers\ProviderAddressMappingController::class, 'destroy' ]);
    Route::post('save-handyman-rating', [ API\BookingController::class, 'saveHandymanRating' ] );
    Route::post('delete-handyman-rating', [ API\BookingController::class, 'deleteHandymanRating' ] );

    Route::get('document-list', [ API\DocumentsController::class, 'getDocumentList' ] );
    Route::get('provider-document-list', [ API\ProviderDocumentController::class, 'getProviderDocumentList' ] );
    Route::post('provider-document-save', [ App\Http\Controllers\ProviderDocumentController::class, 'store' ] );
    Route::post('provider-document-delete/{id}', [ App\Http\Controllers\ProviderDocumentController::class, 'destroy' ] );
    Route::post('provider-document-action',[ App\Http\Controllers\ProviderDocumentController::class, 'action' ]);

    Route::get('tax-list',[ API\CommanController::class, 'getProviderTax' ]);
    Route::get('handyman-dashboard',[ API\DashboardController::class, 'handymanDashboard' ]);

    Route::post('customer-booking-rating',[ API\BookingController::class, 'bookingRatingByCustomer' ]);
    Route::post('handyman-delete/{id}',[ App\Http\Controllers\HandymanController::class, 'destroy' ]);
    Route::post('handyman-action',[ App\Http\Controllers\HandymanController::class, 'action' ]);

    Route::get('provider-payout-list', [ API\PayoutController::class, 'providerPayoutList' ] );
    Route::get('handyman-payout-list', [ API\PayoutController::class, 'handymanPayoutList' ] );

    Route::get('plan-list', [ API\PlanController::class, 'planList' ] );
    Route::post('save-subscription', [ API\SubscriptionController::class, 'providerSubscribe' ] );
    Route::post('cancel-subscription', [ API\SubscriptionController::class, 'cancelSubscription' ] );
    Route::get('subscription-history', [ API\SubscriptionController::class, 'getHistory' ] );
    Route::get('wallet-history', [ API\WalletController::class, 'getHistory' ] );
    Route::post('wallet-top-up', [ API\WalletController::class, 'walletTopup' ] );

    Route::post('save-service-proof', [ API\BookingController::class, 'uploadServiceProof' ] );
    Route::post('handyman-update-available-status',[API\User\UserController::class, 'handymanAvailable']);
    Route::post('delete-user-account',[API\User\UserController::class, 'deleteUserAccount']);
    Route::post('delete-account',[API\User\UserController::class, 'deleteAccount']);

    Route::post('save-post-job',[ App\Http\Controllers\PostJobRequestController::class, 'store' ]);
    Route::post('post-job-delete/{id}', [ App\Http\Controllers\PostJobRequestController::class, 'destroy' ]);

    Route::get('get-post-job',[ API\PostJobRequestController::class, 'getPostRequestList' ]);
    Route::post('get-post-job-detail',[ API\PostJobRequestController::class, 'getPostRequestDetail' ]);

    Route::post('save-bid',[  App\Http\Controllers\PostJobBidController::class, 'store' ]);
    Route::get('get-bid-list',[  API\PostJobBidController::class, 'getPostBidList' ]);


    Route::post('save-provider-slot', [ App\Http\Controllers\ProviderSlotController::class, 'store'] );
    Route::get('get-provider-slot', [API\ProviderSlotController::class, 'getProviderSlot' ] );


    Route::post('package-save',[  App\Http\Controllers\ServicePackageController::class, 'store' ]);
    Route::get('package-list',[API\ServicePackageController::class,'getServicePackageList']);
    Route::post('package-delete/{id}', [ App\Http\Controllers\ServicePackageController::class, 'destroy' ] );



    Route::post('blog-save', [ App\Http\Controllers\BlogController::class, 'store' ] );
    Route::post('blog-delete/{id}', [ App\Http\Controllers\BlogController::class, 'destroy' ] );
    Route::post('blog-action',[ App\Http\Controllers\BlogController::class, 'action' ]);


    Route::post('save-favourite-provider',[ API\ProviderFavouriteController::class, 'saveFavouriteProvider' ]);
    Route::post('delete-favourite-provider',[ API\ProviderFavouriteController::class, 'deleteFavouriteProvider' ]);
    Route::get('user-favourite-provider',[ API\ProviderFavouriteController::class, 'getUserFavouriteProvider' ]);
    Route::post('download-invoice',[API\CommanController::class,'downloadInvoice']);
    Route::get('user-wallet-balance',[API\User\UserController::class,'userWalletBalance']);
    Route::get('get-recently-viewed',[App\Http\Controllers\FrontendSettingController::class,'recentlyViewedGet' ]);

    Route::post('service-addon-save', [ App\Http\Controllers\ServiceAddonController::class, 'store' ] );
    Route::post('service-addon-delete/{id}', [ App\Http\Controllers\ServiceAddonController::class, 'destroy' ] );
    Route::get('service-addon-list', [ API\ServiceAddonController::class, 'getServiceAddonList' ] );

    Route::post('get-wallet-payment-method', [ App\Http\Controllers\WalletController::class, 'getWalletPaymentMethod' ] );
    Route::post('create-wallet-stripe-payment', [ App\Http\Controllers\WalletController::class, 'createWalletStripePayment' ] );
    Route::get('payment-gateway-list',[API\FrontendSettingController::class,'getPaymentGatewayList']);
    Route::get('payment-gateways',[API\PaymentController::class, 'paymentGateways']);

    // E-commerce Authenticated Routes
    // Cart Management
    Route::get('cart', [API\CartController::class, 'index']);
    Route::post('cart/add', [API\CartController::class, 'add']);
    Route::put('cart/{id}', [API\CartController::class, 'update']);
    Route::delete('cart/{id}', [API\CartController::class, 'remove']);
    Route::delete('cart', [API\CartController::class, 'clear']);
    Route::get('cart/count', [API\CartController::class, 'count']);

    // Order Management
    Route::get('orders', [API\OrderController::class, 'index']);
    Route::get('orders/{id}', [API\OrderController::class, 'show']);
    Route::post('orders', [API\OrderController::class, 'create']);
    Route::post('orders/{id}/cancel', [API\OrderController::class, 'cancel']);
    Route::get('orders/{id}/track', [API\OrderController::class, 'track']);

    // Provider Store Management
    Route::get('my-store', [API\StoreController::class, 'myStore']);
    Route::post('my-store', [API\StoreController::class, 'createStore']);
    Route::put('my-store', [API\StoreController::class, 'updateStore']);

    // E-commerce Admin API Routes (with permission checks)
    Route::group(['prefix' => 'ecommerce', 'middleware' => ['permission:product_category list|product list|store list|order list']], function () {

        // Product Categories (Admin & Provider can view, Admin can manage)
        Route::group(['middleware' => ['permission:product_category list']], function () {
            Route::get('product-categories', [App\Http\Controllers\ProductCategoryController::class, 'index_data']);
            Route::post('product-categories', [App\Http\Controllers\ProductCategoryController::class, 'store'])->middleware('permission:product_category add');
            Route::get('product-categories/{id}', [App\Http\Controllers\ProductCategoryController::class, 'show']);
            Route::put('product-categories/{id}', [App\Http\Controllers\ProductCategoryController::class, 'update'])->middleware('permission:product_category edit');
            Route::delete('product-categories/{id}', [App\Http\Controllers\ProductCategoryController::class, 'destroy'])->middleware('permission:product_category delete');
        });

        // Products (Admin can manage all, Provider can manage own)
        Route::group(['middleware' => ['permission:product list']], function () {
            Route::get('products', [App\Http\Controllers\ProductController::class, 'index_data']);
            Route::post('products', [App\Http\Controllers\ProductController::class, 'store'])->middleware('permission:product add');
            Route::get('products/{id}', [App\Http\Controllers\ProductController::class, 'show']);
            Route::put('products/{id}', [App\Http\Controllers\ProductController::class, 'update'])->middleware('permission:product edit');
            Route::delete('products/{id}', [App\Http\Controllers\ProductController::class, 'destroy'])->middleware('permission:product delete');
        });

        // Stores (Admin can manage all, Provider can manage own)
        Route::group(['middleware' => ['permission:store list']], function () {
            Route::get('stores', [App\Http\Controllers\StoreController::class, 'index_data']);
            Route::post('stores', [App\Http\Controllers\StoreController::class, 'store'])->middleware('permission:store add');
            Route::get('stores/{id}', [App\Http\Controllers\StoreController::class, 'show']);
            Route::put('stores/{id}', [App\Http\Controllers\StoreController::class, 'update'])->middleware('permission:store edit');
            Route::delete('stores/{id}', [App\Http\Controllers\StoreController::class, 'destroy'])->middleware('permission:store delete');
            Route::post('stores/{id}/approve', [App\Http\Controllers\StoreController::class, 'approve'])->middleware('permission:store approve');
        });

        // Orders (Admin can manage all, Provider can manage own)
        Route::group(['middleware' => ['permission:order list']], function () {
            Route::get('orders', [App\Http\Controllers\OrderController::class, 'index_data']);
            Route::get('orders/{id}', [App\Http\Controllers\OrderController::class, 'show']);
            Route::put('orders/{id}/status', [App\Http\Controllers\OrderController::class, 'updateStatus'])->middleware('permission:order status update');
            Route::put('orders/{id}/payment-status', [App\Http\Controllers\OrderController::class, 'updatePaymentStatus'])->middleware('permission:order edit');
        });

        // Dynamic Pricing (Admin only)
        Route::group(['middleware' => ['permission:dynamic_pricing list']], function () {
            Route::get('dynamic-pricing', [App\Http\Controllers\DynamicPricingController::class, 'index_data']);
            Route::post('dynamic-pricing/update', [App\Http\Controllers\DynamicPricingController::class, 'updatePricing'])->middleware('permission:dynamic_pricing edit');
        });
    });

});
