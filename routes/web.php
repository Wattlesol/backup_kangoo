<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ProviderTypeController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\PriceCityController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\HandymanController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProviderAddressMappingController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\ProviderDocumentController;
use App\Http\Controllers\RatingReviewController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\EarningController;
use App\Http\Controllers\ProviderPayoutController;
use App\Http\Controllers\HandymanPayoutController;
use App\Http\Controllers\HandymanTypeController;
use App\Http\Controllers\ServiceFaqController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\PostJobRequestController;
use App\Http\Controllers\ServicePackageController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingRatingController;
use App\Http\Controllers\HandymanRatingController;
use App\Http\Controllers\UserServiceListController;
use App\Http\Controllers\ProviderSlotController;
use App\Http\Controllers\NotificationTemplatesController;
use App\Http\Controllers\ServiceAddonController;
use App\Http\Controllers\FrontendSettingController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\DistrictsController;
use App\Http\Controllers\TimeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Provider\StoreController as ProviderStoreController;
use App\Http\Controllers\Provider\ProductController as ProviderProductController;
use App\Http\Controllers\Provider\OrderController as ProviderOrderController;
use App\Http\Controllers\Frontend\ProductController as FrontendProductController;
use App\Http\Controllers\DynamicPricingController;
use App\Http\Controllers\QualityControlController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



require __DIR__.'/auth.php';
require __DIR__.'/frontend.php';

Route::group(['prefix' => 'auth'], function() {
    Route::get('login', [HomeController::class, 'authLogin'])->name('auth.login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('auth.login.post');
    Route::get('register', [HomeController::class, 'authRegister'])->name('auth.register');
    Route::get('recover-password', [HomeController::class, 'authRecoverPassword'])->name('auth.recover-password');
    Route::get('confirm-email', [HomeController::class, 'authConfirmEmail'])->name('auth.confirm-email');
    Route::get('lock-screen', [HomeController::class, 'authlockScreen'])->name('auth.lock-screen');
});

Route::get('lang/{locale}', [HomeController::class,'lang'])->name('switch-language');
Route::get('/verify/{id}', [VerificationController::class, 'verify'])->name('verify');

Route::group(['middleware' => ['auth', 'verified']], function()
{
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::group(['namespace' => '', 'middleware' => ['permission:permission list']], function () {
        Route::resource('permission',PermissionController::class);
        Route::get('permission/add/{type}',[PermissionController::class,'addPermission'])->name('permission.add');
        Route::post('permission/save',[PermissionController::class,'savePermission'])->name('permission.save');

    });

    Route::group(['middleware' => ['permission:role list']], function () {
        Route::resource('role', RoleController::class);
        Route::get('role-index-data',[RoleController::class,'index_data'])->name('role.index_data');
        Route::post('role-bulk-action', [RoleController::class, 'bulk_action'])->name('role.bulk-action');
    });

    Route::get('changeStatus', [ HomeController::class, 'changeStatus'])->name('changeStatus');

    Route::group(['middleware' => ['permission:category list']], function () {
        Route::resource('category', CategoryController::class);
        Route::get('index_data',[CategoryController::class,'index_data'])->name('category.index_data');
        Route::post('category-bulk-action', [CategoryController::class, 'bulk_action'])->name('category.bulk-action');
        Route::post('category-action',[CategoryController::class, 'action'])->name('category.action');
        Route::post('category/{id}', [CategoryController::class, 'destroy'])->name('category.destroy');
        Route::post('check-in-trash', [CategoryController::class, 'check_in_trash'])->name('check-in-trash');

    });

    Route::group(['middleware' => ['permission:service list']], function () {
        Route::resource('service', ServiceController::class);
        Route::get('service-index-data',[ServiceController::class,'index_data'])->name('service.service-index-data');
        Route::post('service-bulk-action', [ServiceController::class, 'bulk_action'])->name('service.bulk-action');
        Route::get('user-service-list',[ServiceController::class,'getUserServiceList'])->name('service.user-service-list');
        Route::post('service-action',[ServiceController::class, 'action'])->name('service.action');
        Route::post('service/{id}', [ServiceController::class, 'destroy'])->name('service.destroy');
        Route::get('user-service-index-data',[UserServiceListController::class,'index_data'])->name('service.user-index-data');

    });
    Route::get('provider-change-password', [ ProviderController::class , 'getChangePassword'])->name('provider.getchangepassword');
    Route::post('provider-change-password', [ ProviderController::class , 'changePassword'])->name('provider.changepassword');
    Route::get('provider-time-slot/{id}',[ProviderController::class,'getProviderTimeSlot'])->name('provider.time-slot');
    Route::get('provider-edit-time-slot',[ProviderController::class,'editProviderTimeSlot'])->name('provider.edit-time-slot');
    Route::post('provider-save-slot', [ProviderSlotController::class, 'store'] )->name('providerslot.store');
    Route::group(['middleware' => ['permission:provider list']], function () {
        Route::resource('provider', ProviderController::class);
        Route::get('provider/list/{status?}', [ProviderController::class,'index'])->name('provider.pending');
        Route::get('provider-index-data',[ProviderController::class,'index_data'])->name('provider.index_data');
        Route::get('provider/approve/{id}',[ProviderController::class, 'approve'])->name('provider.approve');
        Route::post('provider-action',[ProviderController::class, 'action'])->name('provider.action');
        Route::post('provider/{id}', [ProviderController::class, 'destroy'])->name('provider.destroy');
        Route::post('provider-bulk-action', [ProviderController::class, 'bulk_action'])->name('provider.bulk-action');
    });

    Route::group(['middleware' => ['permission:provideraddress list']], function () {
        Route::resource('provideraddress', ProviderAddressMappingController::class);
        Route::get('provideraddress-index-data',[ProviderAddressMappingController::class,'index_data'])->name('provideraddress.index_data');
        Route::post('provideraddress-bulk-action', [ProviderAddressMappingController::class, 'bulk_action'])->name('provideraddress.bulk-action');
        Route::post('provideraddress/{id}', [ProviderAddressMappingController::class, 'destroy'])->name('provideraddress.destroy');
        Route::post('/get-lat-long', [ProviderAddressMappingController::class, 'getLatLong'])->name('getLatLong');
    });

    Route::group(['middleware' => ['permission:providertype list']], function () {
        Route::resource('providertype', ProviderTypeController::class);
        Route::get('providertype-index-data',[ProviderTypeController::class,'index_data'])->name('providertype.index_data');
        Route::post('providertype-bulk-action', [ProviderTypeController::class, 'bulk_action'])->name('providertype.bulk-action');
        Route::post('providertype-action',[ProviderTypeController::class, 'action'])->name('providertype.action');
        Route::post('providertype/{id}', [ProviderTypeController::class, 'destroy'])->name('providertype.destroy');
    });
    Route::get('handyman-change-password', [ HandymanController::class , 'getChangePassword'])->name('handyman.getchangepassword');
    Route::post('handyman-change-password', [ HandymanController::class , 'changePassword'])->name('handyman.changepassword');
    Route::group(['middleware' => ['permission:handyman list']], function () {
        Route::resource('handyman', HandymanController::class);
        Route::get('handyman/list/{status?}', [HandymanController::class,'index'])->name('handyman.pending');
        Route::get('handyman-index-data',[HandymanController::class,'index_data'])->name('handyman.index_data');
        Route::post('handyman-bulk-action', [HandymanController::class, 'bulk_action'])->name('handyman.bulk-action');
        Route::get('handyman/approve/{id}',[ProviderController::class, 'approve'])->name('handyman.approve');
        Route::post('handyman-action',[HandymanController::class, 'action'])->name('handyman.action');
        Route::post('handyman/{id}', [HandymanController::class, 'destroy'])->name('handyman.destroy');
        Route::post('assign-provider', [HandymanController::class, 'updateProvider'])->name('handyman.updateProvider');
        Route::get('handymandetail/{id}', [HandymanController::class, 'handyman_detail'])->name('handyman.detail');
    });

    Route::group(['middleware' => ['permission:coupon list']], function () {
        Route::resource('coupon', CouponController::class);
        Route::get('coupon-index_data',[CouponController::class,'index_data'])->name('coupon.index_data');
        Route::post('coupon-bulk-action', [CouponController::class, 'bulk_action'])->name('coupon.bulk-action');
        Route::post('coupons-action',[CouponController::class, 'action'])->name('coupon.action');
        Route::post('coupon/{id}', [CouponController::class, 'destroy'])->name('coupon.destroy');
    });

    Route::group(['middleware' => ['permission:booking list']], function () {
        Route::resource('booking', BookingController::class);
        Route::get('booking-index-data',[BookingController::class,'index_data'])->name('booking.index_data');
        Route::post('booking-bulk-action', [BookingController::class, 'bulk_action'])->name('booking.bulk-action');
        Route::post('booking-status-update',[ BookingController::class,'updateStatus'])->name('bookingStatus.update');
        Route::post('booking-save', [ App\Http\Controllers\BookingController::class, 'store' ] )->name('booking.save');
        Route::post('booking-action',[BookingController::class, 'action'])->name('booking.action');
        Route::post('booking/{id}', [BookingController::class, 'destroy'])->name('booking.destroy');
        Route::get('package_service_detail/{id}', [BookingController::class, 'package_service_show'])->name('booking.package_service_show');
        Route::get('package_service_renew_data/{id}', [BookingController::class, 'package_service_renew_data'])->name('booking.package_service_renew_data');
        Route::get('package_service_booking', [BookingController::class, 'package_service_detail'])->name('booking.package_service_detail');
        Route::post('add_new_car/{id}', [BookingController::class, 'add_new_car'])->name('booking.add_new_car');
        Route::post('add_new_address/{id}', [BookingController::class, 'add_new_address'])->name('booking.add_new_address');
        Route::get('subscription_data/{service_id}/{id}', [BookingController::class, 'booking_service_data'])->name('booking.booking_service_data');
        Route::post('booking_car_data/{car_id}/{package_id}/{service_id}', [BookingController::class, 'booking_car_data'])->name('booking.booking_car_data');
        Route::post('booking_breaks_data/{package_id}/{service_id}', [BookingController::class, 'booking_breaks_data'])->name('booking.booking_breaks_data');
        Route::get('booking_breaks_data_with_out_Data/{package_id}/{service_id}', [BookingController::class, 'booking_breaks_data_with_out_Data'])->name('booking.booking_breaks_data_with_out_Data');
        Route::get('Handyman_booking',[ServicePackageController::class,'Handyman_booking'])->name('servicepackage.Handyman_booking');
        Route::post('servicepackage/rate/{service_id}', [ServicePackageController::class,'rate'])->name('servicepackage.rate');
        Route::get('booking/view/rate/{service_id}', [ServicePackageController::class,'view_rate'])->name('servicepackage.rate_view');
        Route::post('user_booking_service/rate/{service_id}', [ServicePackageController::class,'user_booking_service'])->name('servicepackage.user_booking_service');
        Route::post('servicepsacksage/ChangeDatas/{booking_id?}', [BookingController::class,'ChangeData'])->name('servicepackasge.user_change');
        Route::get('servicepassckage/change_stsatus/{booking_id?}', [ServicePackageController::class,'change_status_user'])->name('user_change.change_status');
        Route::post('booking/complaint/{booking_id?}', [ServicePackageController::class,'submitComplaint'])->name('user_change.complaint_post');
        Route::post('complaint/reply/{booking_id?}', [ServicePackageController::class,'reply_submitComplaint'])->name('user_change.reply_submitComplaint');
        Route::get('show/complaint', [ServicePackageController::class,'complaint'])->name('users.complaint');
        Route::get('show/complaint/data/{complaint_id?}', [ServicePackageController::class,'complaint_show'])->name('users.complaint_show');
        Route::get('proiver_booking',[ServicePackageController::class,'proiver_booking'])->name('servicepackage.proiver_booking');
        Route::get('proiver_booking_servicepackage/change_status/{booking_id?}/{status?}', [ServicePackageController::class,'change_status'])->name('proiver_booking_servicepackage.change_status');
        Route::get('handy_man_change/change_status/{booking_id?}/{status?}', [ServicePackageController::class,'change_status'])->name('handy_man_change.change_status');
        Route::get('handy_man_change/start_service/{booking_id?}', [ServicePackageController::class,'start_service'])->name('handy_man_change.start_service');
        Route::post('proiver_booking_servicepackage/ChangeData/{booking_id?}', [ServicePackageController::class,'ChangeData'])->name('proiver_booking_servicepackage.ChangeData');
        Route::post('proiver_booking_servicepackage/AssignHandyman/{booking_id?}', [ServicePackageController::class,'AssignHandyman'])->name('proiver_booking_servicepackage.AssignHandyman');
        Route::post('/proof-image-comment', [ServicePackageController::class, 'storeProofImageComment'])->name('proof_image_comment.store');




        Route::get('provider/show/complaint', [ServicePackageController::class,'complaint_provider'])->name('users.complaint_provider');
        Route::get('provider/show/complaint/data/{complaint_id?}', [ServicePackageController::class,'complaint_show'])->name('users.complaint_show_provider');

    });

    Route::group(['middleware' => ['permission:slider list']], function () {
        Route::resource('slider', SliderController::class);
        Route::get('slider-index-data',[SliderController::class,'index_data'])->name('slider.index_data');
        Route::post('slider-bulk-action', [SliderController::class, 'bulk_action'])->name('slider.bulk-action');
        Route::post('slider-action',[SliderController::class, 'action'])->name('slider.action');
        Route::post('slider/{id}', [SliderController::class, 'destroy'])->name('slider.destroy');
    });

    Route::resource('payment', PaymentController::class);
    Route::get('cash-payment-list', [PaymentController::class,'cashDatatable'])->name('cash.list');
    Route::get('cash-index-data', [PaymentController::class,'cash_index_data'])->name('cash.index_data');
    Route::get('payment-index-data',[PaymentController::class,'index_data'])->name('payment.index_data');
    Route::post('payment-bulk-action', [PaymentController::class, 'bulk_action'])->name('payment.bulk-action');
    Route::get('cash/history/{id?}', [PaymentController::class,'cashIndex'])->name('cash.index');
    Route::get('paymenthistory-index-data/{id}', [PaymentController::class,'paymenthistory_index_data'])->name('paymenthistory.index_data');
    Route::get('cash/approve/{id}',[PaymentController::class, 'cashApprove'])->name('cash.approve');

    Route::post('save-payment',[App\Http\Controllers\API\PaymentController::class, 'savePayment'])->name('payment.save');
    Route::get('save-stripe-payment/{id}',[App\Http\Controllers\BookingController::class, 'saveStripePayment']);



    Route::get('user-change-password', [ CustomerController::class , 'getChangePassword'])->name('user.getchangepassword');
    Route::post('user-change-password', [ CustomerController::class , 'changePassword'])->name('user.changepassword');
    Route::group(['middleware' => ['permission:user list']], function () {
        Route::resource('user', CustomerController::class);
        Route::get('user/list/{status?}', [CustomerController::class,'index'])->name('user.all');
        Route::get('user-index-data',[CustomerController::class,'index_data'])->name('user.index_data');
        Route::post('user-bulk-action', [CustomerController::class, 'bulk_action'])->name('user.bulk-action');
        Route::post('user-action',[CustomerController::class, 'action'])->name('user.action');
        Route::post('user/{id}', [CustomerController::class, 'destroy'])->name('user.destroy');
        Route::get('export_data_to_excel', [CustomerController::class, 'export_data_to_excel'])->name('user.export_data_to_excel');
    });

    Route::get('booking-assign-form/{id}',[BookingController::class,'bookingAssignForm'])->name('booking.assign_form');
    Route::get('booking/details/{id}',[BookingController::class,'bookingDetails'])->name('booking.details');
    Route::post('booking-assigned',[BookingController::class,'bookingAssigned'])->name('booking.assigned');
    Route::get('comission/{id}',[SettingController::class,'comission'])->name('setting.comission');
    Route::get('details/{id}',[BookingController::class,'bookingDetailsData'])->name('booking.detailsdata');


    // Setting
    Route::get('setting/{page?}',[ SettingController::class, 'settings'])->name('setting.index');
    Route::post('/layout-page',[ SettingController::class, 'layoutPage'])->name('layout_page');

    // Route::post('settings/save',[ SettingController::class , 'settingsUpdates'])->name('settingsUpdates');
    // Route::post('dashboard-setting',[ SettingController::class , 'dashboardtogglesetting'])->name('togglesetting');
    // Route::post('provider-dashboard-setting',[ SettingController::class , 'providerdashboardtogglesetting'])->name('providertogglesetting');
    // Route::post('handyman-dashboard-setting',[ SettingController::class , 'handymandashboardtogglesetting'])->name('handymantogglesetting');
    // Route::post('config-save',[ SettingController::class , 'configUpdate'])->name('configUpdate');


    Route::post('env-setting', [ SettingController::class , 'envChanges'])->name('envSetting');
    Route::post('update-profile', [ SettingController::class , 'updateProfile'])->name('updateProfile');
    Route::post('change-password', [ SettingController::class , 'changePassword'])->name('changePassword');


    //Frontend Setting

    Route::middleware(['auth', 'role:admin|demo_admin'])->group(function () {
        Route::get('frontend-setting/{page?}', [FrontendSettingController::class, 'frontendSettings'])->name('frontend_setting.index');
        Route::post('/layout-frontend-page', [FrontendSettingController::class, 'layoutPage'])->name('layout_frontend_page');
        Route::post('/landing-page-settings-updates', [FrontendSettingController::class, 'landingpagesettingsUpdates'])->name('landing_page_settings_updates');
        Route::post('/landing-layout-page', [FrontendSettingController::class, 'landingLayoutPage'])->name('landing_layout_page');
        Route::post('/get-landing-layout-page-config', [FrontendSettingController::class , 'getLandingLayoutPageConfig'])->name('getLandingLayoutPageConfig');
        Route::post('/header-page-settings', [FrontendSettingController::class, 'headingpagesettings'])->name('heading_page_settings');
        Route::post('/footer-page-settings', [FrontendSettingController::class, 'footerpagesettings'])->name('footer_page_settings');
        Route::post('/login-register-page-settings', [FrontendSettingController::class, 'loginregisterpagesettings'])->name('login_register_page_settings');
    });

    Route::get('notification-list',[ NotificationController::class ,'notificationList'])->name('notification.list');
    Route::get('notification-counts',[ NotificationController::class ,'notificationCounts'])->name('notification.counts');
    Route::get('notification',[ NotificationController::class ,'index'])->name('notification.index');
    Route::get('notification-index-data',[ NotificationController::class ,'index_data'])->name('notification.index_data');

    Route::post('remove-file', [ App\Http\Controllers\HomeController::class, 'removeFile' ] )->name('remove.file');
    Route::post('get-lang-file', [ App\Http\Controllers\LanguageController::class, 'getFile' ] )->name('getLangFile');
    Route::post('save-lang-file', [ App\Http\Controllers\LanguageController::class, 'saveFileContent' ] )->name('saveLangContent');

    Route::group(['middleware' => ['permission:terms condition']], function () {
        Route::get('pages/term-condition',[ SettingController::class, 'termAndCondition'])->name('term-condition');
        Route::post('term-condition-save',[ SettingController::class, 'saveTermAndCondition'])->name('term-condition-save');
    });

    Route::group(['middleware' => ['permission:privacy policy']], function () {
        Route::get('pages/privacy-policy',[ SettingController::class, 'privacyPolicy'])->name('privacy-policy');
        Route::post('privacy-policy-save',[ SettingController::class, 'savePrivacyPolicy'])->name('privacy-policy-save');
    });

    Route::get('pages/help-support',[ SettingController::class, 'helpAndSupport'])->name('help-support');
    Route::post('help-support-save',[ SettingController::class, 'saveHelpAndSupport'])->name('help-support-save');

    Route::get('pages/refund-cancellation-policy',[ SettingController::class, 'refundCancellationPolicy'])->name('refund-cancellation-policy');
    Route::post('refund-cancellation-policy-save',[ SettingController::class, 'saveRefundCancellationPolicy'])->name('refund-cancellation-policy-save');

    Route::post('general-setting-save',[ SettingController::class, 'generalSetting'])->name('generalsetting');
    Route::post('theme-setup-save',[ SettingController::class, 'themeSetup'])->name('themesetup');
    Route::post('site-setup-save',[ SettingController::class, 'siteSetup'])->name('sitesetup');
    Route::post('service-config-save',[ SettingController::class, 'serviceConfig'])->name('serviceConfig');
    Route::post('social-media-save',[ SettingController::class, 'socialMedia'])->name('socialMedia');
    route::post('role-permission',[RoleController::class,'rolePermission'])->name('role_layout_page');
    Route::post('cookie-setup-save',[ SettingController::class, 'cookieSetup'])->name('cookiesetup');

    Route::group(['middleware' => ['permission:document list|providerdocument list']], function () {
        Route::resource('document', DocumentsController::class);
        Route::get('document-index-data',[DocumentsController::class,'index_data'])->name('document.index_data');
        Route::post('document-bulk-action', [DocumentsController::class, 'bulk_action'])->name('document.bulk-action');
        Route::post('document-action',[DocumentsController::class, 'action'])->name('document.action');
        Route::post('document/{id}', [DocumentsController::class, 'destroy'])->name('document.destroy');
    });

    Route::group(['middleware' => ['permission:providerdocument list']], function () {
        Route::resource('providerdocument', ProviderDocumentController::class);
        Route::get('providerdocument-index-data',[ProviderDocumentController::class,'index_data'])->name('providerdocument.index_data');
        Route::post('providerdocument-bulk-action', [ProviderDocumentController::class, 'bulk_action'])->name('providerdocument.bulk-action');
        Route::post('providerdocument-action',[ProviderDocumentController::class, 'action'])->name('providerdocument.action');
        Route::post('providerdocument/{id}', [ProviderDocumentController::class, 'destroy'])->name('providerdocument.destroy');
    });

    Route::resource('ratingreview', RatingReviewController::class);
    Route::post('ratingreview-action',[RatingReviewController::class, 'action'])->name('ratingreview.action');
    Route::get('ratingreview-index-data',[RatingReviewController::class,'index_data'])->name('ratingreview.index_data');

    Route::resource('booking-rating', BookingRatingController::class);
    Route::get('booking-rating-index-data',[BookingRatingController::class,'index_data'])->name('booking-rating.index_data');
    Route::post('booking-rating-bulk-action', [BookingRatingController::class, 'bulk_action'])->name('booking-rating.bulk-action');
    Route::post('booking-rating/{id}', [BookingController::class, 'destroy'])->name('booking-rating.destroy');
    Route::post('booking-rating-action',[CouponController::class, 'action'])->name('booking-rating.action');

    Route::resource('handyman-rating', HandymanRatingController::class);
    Route::get('handyman-rating-index-data',[HandymanRatingController::class,'index_data'])->name('handyman-rating.index_data');
    Route::post('handyman-rating-bulk-action', [HandymanRatingController::class, 'bulk_action'])->name('handyman-rating.bulk-action');
    Route::post('handyman-rating/{id}', [HandymanController::class, 'destroy'])->name('handyman-rating.destroy');

    Route::post('/payment-layout-page',[ PaymentGatewayController::class, 'paymentPage'])->name('payment_layout_page');
    Route::post('payment-settings/save',[ PaymentGatewayController::class , 'paymentsettingsUpdates'])->name('paymentsettingsUpdates');
    Route::post('get_payment_config',[ PaymentGatewayController::class , 'getPaymentConfig'])->name('getPaymentConfig');

    Route::post('/razorpay-layout-page',[ PaymentGatewayController::class, 'rezorpaypaymentPage'])->name('razorpay_layout_page');

    Route::resource('tax', TaxController::class);
    Route::get('tax-index_data',[TaxController::class,'index_data'])->name('tax.index_data');
    Route::post('tax-bulk-action', [TaxController::class, 'bulk_action'])->name('tax.bulk-action');
    Route::post('tax/{id}', [TaxController::class, 'destroy'])->name('tax.destroy');
    Route::get('earning',[EarningController::class,'index'])->name('earning');
    Route::get('earning-data',[EarningController::class,'setEarningData'])->name('earningData');
    Route::post('earning/{id}', [EarningController::class, 'destroy'])->name('earning.destroy');
    Route::get('earning/{id}', [EarningController::class, 'show'])->name('earning.show');



    //region//
    Route::group(['prefix' => 'region','as' => 'region.'], function () {
        Route::get('/', [RegionController::class, 'index'])->name('index');
        Route::get('/create', [RegionController::class, 'create'])->name('create');
        Route::post('/store', [RegionController::class, 'store'])->name('store');
        Route::get('/edit/{id?}', [RegionController::class, 'edit'])->name('edit');
        Route::put('/update/{id?}', [RegionController::class, 'update'])->name('update');
        Route::get('/destroy/{id?}', [RegionController::class, 'destroy'])->name('destroy');
    });
    //region//


    //TimeController//
    Route::group(['prefix' => 'time','as' => 'time.'], function () {
        Route::get('/', [TimeController::class, 'index'])->name('index');
        Route::get('/create', [TimeController::class, 'create'])->name('create');
        Route::post('/store', [TimeController::class, 'store'])->name('store');
        Route::get('/edit/{id?}', [TimeController::class, 'edit'])->name('edit');
        Route::put('/update/{id?}', [TimeController::class, 'update'])->name('update');
        Route::get('/destroy/{id?}', [TimeController::class, 'destroy'])->name('destroy');
    });
    //TimeController//

    //districts//
    Route::group(['prefix' => 'districts','as' => 'districts.'], function () {
        Route::get('/', [DistrictsController::class, 'index'])->name('index');
        Route::get('/create', [DistrictsController::class, 'create'])->name('create');
        Route::post('/store', [DistrictsController::class, 'store'])->name('store');
        Route::get('/edit/{id?}', [DistrictsController::class, 'edit'])->name('edit');
        Route::put('/update/{id?}', [DistrictsController::class, 'update'])->name('update');
        Route::get('/destroy/{id?}', [DistrictsController::class, 'destroy'])->name('destroy');
    });
    //districts//

    //city//
    Route::group(['prefix' => 'city','as' => 'city.'], function () {
        Route::get('/', [CityController::class, 'index'])->name('index');
        Route::get('/create', [CityController::class, 'create'])->name('create');
        Route::post('/store', [CityController::class, 'store'])->name('store');
        Route::get('/edit/{id?}', [CityController::class, 'edit'])->name('edit');
        Route::put('/update/{id?}', [CityController::class, 'update'])->name('update');
        Route::get('/destroy/{id?}', [CityController::class, 'destroy'])->name('destroy');
    });
    //city//

    //pricelist//
    Route::group(['prefix' => 'pricelist','as' => 'pricelist.'], function () {
        Route::get('/', [PriceListController::class, 'index'])->name('index');
        Route::get('/create', [PriceListController::class, 'create'])->name('create');
        Route::post('/store', [PriceListController::class, 'store'])->name('store');
        Route::get('/edit/{id?}', [PriceListController::class, 'edit'])->name('edit');
        Route::put('/update/{id?}', [PriceListController::class, 'update'])->name('update');
        Route::get('/destroy/{id?}', [PriceListController::class, 'destroy'])->name('destroy');
    });
    //pricelist//

    //pricecity//
    Route::group(['prefix' => 'pricecity','as' => 'pricecity.'], function () {
        Route::get('/', [PriceCityController::class, 'index'])->name('index');
        Route::get('/create', [PriceCityController::class, 'create'])->name('create');
        Route::post('/store', [PriceCityController::class, 'store'])->name('store');
        Route::get('/edit/{id?}', [PriceCityController::class, 'edit'])->name('edit');
        Route::put('/update/{id?}', [PriceCityController::class, 'update'])->name('update');
        Route::get('/destroy/{id?}', [PriceCityController::class, 'destroy'])->name('destroy');
    });
    //pricecity//


    Route::get('handyman-earning',[EarningController::class,'handymanEarning'])->name('handymanEarning');
    Route::get('handyman-earning-data',[EarningController::class,'handymanEarningData'])->name('handymanEarningData');

    Route::resource('providerpayout', ProviderPayoutController::class);
    Route::get('providerpayout-index-data',[ProviderPayoutController::class,'index_data'])->name('providerpayout.index_data');
    Route::post('providerpayout-bulk-action', [ProviderPayoutController::class, 'bulk_action'])->name('providerpayout.bulk-action');
    Route::get('providerpayout/create/{id}', [ProviderPayoutController::class,'create'])->name('providerpayout.create');
    Route::get('provider-payout-index-data/{id}',[ProviderPayoutController::class,'ProviderPayout_index_data'])->name('providerpayout.ProviderPayout_index_data');

    Route::get('review/{id}',[ProviderController::class,'review'])->name('provider.review');
    Route::post('sidebar-reorder-save',[ SettingController::class, 'sequenceSave'])->name('reorderSave');

    Route::resource('handymanpayout', HandymanPayoutController::class);
    Route::get('handymanpayout-index-data',[HandymanPayoutController::class,'index_data'])->name('handymanpayout.index_data');
    Route::post('handymanpayout-bulk-action', [HandymanPayoutController::class, 'bulk_action'])->name('handymanpayout.bulk-action');
    Route::get('handymanpayout/create/{id}', [HandymanPayoutController::class,'create'])->name('handymanpayout.create');
    Route::get('handymandocument/{id}', [HandymanPayoutController::class,'document'])->name('handymandata.document');
    Route::post('handymandocument/{id}', [HandymanPayoutController::class,'documentstore'])->name('handymandata.document_store');

    Route::group(['middleware' => ['permission:handymantype list']], function () {
        Route::resource('handymantype', HandymanTypeController::class);
        Route::get('handyman-index_data',[HandymanTypeController::class,'index_data'])->name('handymantype.index_data');
        Route::post('handymantype-bulk-action', [HandymanTypeController::class, 'bulk_action'])->name('handymantype.bulk-action');
        Route::post('handymantype-action',[HandymanTypeController::class, 'action'])->name('handymantype.action');
        Route::post('handymantype/{id}', [HandymanTypeController::class, 'destroy'])->name('handymantype.destroy');
    });

    Route::group(['middleware' => ['permission:servicefaq list']], function () {
        Route::resource('servicefaq', ServiceFaqController::class);
        Route::get('servicefaq-index-data',[ServiceFaqController::class,'index_data'])->name('servicefaq.index_data');
    });
    Route::match(['get', 'post'], '/push-notification', [SettingController::class, 'PushNotification'])->name('pushNotification.index');
    Route::post('send-push-notification', [ SettingController::class , 'sendPushNotification'])->name('sendPushNotification');
    Route::post('save-earning-setting', [ SettingController::class , 'saveEarningTypeSetting'])->name('saveEarningTypeSetting');
    // Route::post('advance-earning-setting' , [ SettingController::class , 'advanceEarningSetting'])->name('advanceEarningSetting');
    Route::post('other-setting' , [ SettingController::class , 'otherSetting'])->name('otherSetting');

    // Route::post('enable-user-wallet', [SettingController::class, 'enableUserWallet'])->name('enableUserWallet');

    Route::resource('wallet', WalletController::class);
    Route::get('wallet-index-data',[WalletController::class,'index_data'])->name('wallet.index_data');
    Route::post('wallet-bulk-action', [WalletController::class, 'bulk_action'])->name('wallet.bulk-action');
    Route::post('wallet/{id}', [WalletController::class, 'destroy'])->name('wallet.destroy');
    Route::get('wallet-history-index-data/{id}',[WalletController::class,'wallethistory_index_data'])->name('wallethistory.index_data');

    Route::group(['middleware' => ['permission:subcategory list']], function () {
        Route::resource('subcategory', SubCategoryController::class);
        Route::get('sub-index-data',[SubCategoryController::class,'index_data'])->name('subcategory.sub-index-data');
        Route::post('sub-bulk-action', [SubCategoryController::class, 'bulk_action'])->name('sub-bulk-action');
        Route::post('subcategory-action',[SubCategoryController::class, 'action'])->name('subcategory.action');
        Route::post('subcategory/{id}', [SubCategoryController::class, 'destroy'])->name('subcategory.destroy');
    });

    Route::resource('plans', PlanController::class);
    Route::get('plans-index-data',[PlanController::class,'index_data'])->name('plans.index_data');
    Route::post('plans-bulk-action', [PlanController::class, 'bulk_action'])->name('plans.bulk-action');
    Route::post('plans/{id}', [PlanController::class, 'destroy'])->name('plans.destroy');

    Route::resource('bank',BankController::class);
    Route::get('bank-index-data',[BankController::class, 'index_data'])->name('bank.index_data');
    Route::post('bank-bulk-action',[BankController::class,'bulk_action'])->name('bank.bulk_action');
    Route::post('bank-action',[BankController::class, 'action'])->name('bank.action');
    Route::get('bank/create/', [BankController::class,'create'])->name('bank.create');
    Route::get('bank-list/{providerbank}',[BankController::class, 'banklist'])->name('bank.list');


    Route::get('/provider-detail-page',[ ProviderController::class, 'providerDetail'])->name('provider_detail_pages');
    Route::post('/provider-detail-page',[ ProviderController::class, 'providerDetail'])->name('provider_detail_pages');
    Route::post('/booking-layout-page/{id}',[ BookingController::class, 'bookingstatus'])->name('booking_layout_page');
    Route::get('/invoice_pdf/{id}', [BookingController::class, 'createPDF'])->name('invoice_pdf');

    Route::group(['middleware' => ['permission:postjob list']], function () {
        Route::resource('post-job-request', PostJobRequestController::class);
        Route::get('post-job-index-data',[PostJobRequestController::class,'index_data'])->name('post-job.index_data');
        Route::post('post-job-bulk-action', [PostJobRequestController::class, 'bulk_action'])->name('post-job.bulk-action');
        Route::get('post-job-service/list/{postjobid?}', [ServiceController::class, 'index'])->name('postjobrequest.service');
        Route::get('postrequest-index-data/{id}', [PostJobRequestController::class,'postrequest_index_data'])->name('postrequest.index_data');
    });

    Route::group(['middleware' => ['permission:servicepackage list']], function () {
        Route::resource('servicepackage', ServicePackageController::class);
        Route::get('servicepackage/list/{packageid?}', [ServiceController::class,'index'])->name('servicepackage.service');
        Route::get('servicepackage-index-data',[ServicePackageController::class,'index_data'])->name('servicepackage.index-data');
        Route::post('servicepackage-bulk-action', [ServicePackageController::class, 'bulk_action'])->name('servicepackage.bulk-action');
        Route::post('servicepackage-action',[ServicePackageController::class, 'action'])->name('servicepackage.action');

        Route::get('servicepackage_booking',[ServicePackageController::class,'servicepackage_booking'])->name('servicepackage.servicepackage_booking');
        Route::get('servicepackage/change_status/{booking_id?}/{status?}', [ServicePackageController::class,'change_status'])->name('servicepackage.change_status');
        Route::post('servicepackage/ChangeData/{booking_id?}', [ServicePackageController::class,'ChangeData'])->name('servicepackage.ChangeData');
        Route::post('servicepackage/AssignHandyman/{booking_id?}', [ServicePackageController::class,'AssignHandyman'])->name('servicepackage.AssignHandyman');

    });

    Route::group(['middleware' => ['permission:blog list']], function () {
        Route::resource('blog', BlogController::class);
        Route::get('blog-index-data',[BlogController::class,'index_data'])->name('blog.index_data');
        Route::post('blog-bulk-action', [BlogController::class, 'bulk_action'])->name('blog.bulk-action');
        Route::post('blog-action',[BlogController::class, 'action'])->name('blog.action');
        Route::post('blog/{id}', [BlogController::class, 'destroy'])->name('blog.destroy');
    });


    Route::group(['middleware' => ['permission:handyman list']], function () {
        Route::get('complaint',[QualityControlController::class,'index'])->name('complaint.index_data');
        Route::post('complaint',[QualityControlController::class,'store'])->name('complaint.store');
        Route::get('complaint/{id}', [QualityControlController::class, 'show'])->name('complaint.show');
        Route::post('reply_submitComplaint/{id}', [QualityControlController::class, 'reply_submitComplaint'])->name('complaint.reply_submitComplaint');
    });

    Route::group(['middleware' => ['permission:service list']], function () {
        Route::resource('serviceaddon', ServiceAddonController::class);
        Route::get('serviceaddon-index-data',[ServiceAddonController::class,'index_data'])->name('serviceaddon.index-data');
        Route::post('serviceaddon-bulk-action', [ServiceAddonController::class, 'bulk_action'])->name('serviceaddon.bulk-action');
    });

    Route::group(['prefix' => 'notifications-templates', 'as' => 'notificationtemplates.'], function () {
        Route::get('index_list', [NotificationTemplatesController::class, 'index_list'])->name('index_list');
        Route::get('index_data', [NotificationTemplatesController::class, 'index_data'])->name('index_data');
        Route::get('trashed', [NotificationTemplatesController::class, 'trashed'])->name('trashed');
        Route::patch('trashed/{id}', [NotificationTemplatesController::class, 'restore'])->name('restore');
        Route::get('ajax-list', [NotificationTemplatesController::class, 'getAjaxList'])->name('ajax-list');
        Route::get('notification-buttons', [NotificationTemplatesController::class, 'notificationButton'])->name('notification-buttons');
        Route::get('notification-template', [NotificationTemplatesController::class, 'notificationTemplate'])->name('notification-template');
        Route::post('channels-update', [NotificationTemplatesController::class, 'updateChanels'])->name('settings.update');
        Route::post('update-status/{id}', [NotificationTemplatesController::class, 'update_status'])->name('update_status');

    });
    Route::post('notification-template-bulk-action', [NotificationTemplatesController::class, 'bulk_action'])->name('notificationtemplate.bulk_action');
    Route::resource('notification-templates', NotificationTemplatesController::class, ['names' => 'notification-templates']);
    Route::get('save-wallet-stripe-payment/{id}',[App\Http\Controllers\WalletController::class, 'saveWalletStripePayment']);

    // E-commerce Routes
    Route::group(['middleware' => ['permission:product_category list']], function () {
        Route::resource('productcategory', ProductCategoryController::class);
        Route::get('productcategory-index-data',[ProductCategoryController::class,'index_data'])->name('productcategory.index_data');
        Route::post('productcategory-action',[ProductCategoryController::class, 'action'])->name('productcategory.action');
        Route::post('productcategory/{id}', [ProductCategoryController::class, 'destroy'])->name('productcategory.destroy');
    });

    Route::group(['middleware' => ['permission:product list']], function () {
        Route::resource('product', ProductController::class);
        Route::get('product-index-data',[ProductController::class,'index_data'])->name('product.index_data');
        Route::post('product-action',[ProductController::class, 'action'])->name('product.action');
        Route::post('product/{id}', [ProductController::class, 'destroy'])->name('product.destroy');
        Route::post('product-update-pricing', [ProductController::class, 'updatePricing'])->name('product.update-pricing');
    });

    Route::group(['middleware' => ['permission:store list']], function () {
        Route::resource('store', StoreController::class);
        Route::get('store-index-data',[StoreController::class,'index_data'])->name('store.index_data');
        Route::post('store-action',[StoreController::class, 'action'])->name('store.action');
        Route::post('store/{id}', [StoreController::class, 'destroy'])->name('store.destroy');
        Route::post('store-approve', [StoreController::class, 'approve'])->name('store.approve');
        Route::get('store-pending', [StoreController::class, 'pending'])->name('store.pending');
        Route::get('store-pending-data',[StoreController::class,'pending_data'])->name('store.pending_data');
    });

    Route::group(['middleware' => ['permission:order list']], function () {
        Route::resource('order', OrderController::class);
        Route::get('order-index-data',[OrderController::class,'index_data'])->name('order.index_data');
        Route::post('order-update-status', [OrderController::class, 'updateStatus'])->name('order.update-status');
        Route::post('order-update-payment-status', [OrderController::class, 'updatePaymentStatus'])->name('order.update-payment-status');
        Route::post('order-cancel', [OrderController::class, 'cancel'])->name('order.cancel');
        Route::get('order-statistics', [OrderController::class, 'statistics'])->name('order.statistics');
        Route::post('order-bulk-action', [OrderController::class, 'bulkAction'])->name('order.bulk-action');
        Route::get('order-export', [OrderController::class, 'export'])->name('order.export');
    });

    // Dynamic Pricing Routes (Admin only)
    Route::group(['middleware' => ['permission:dynamic_pricing list']], function () {
        Route::get('dynamic-pricing', [DynamicPricingController::class, 'index'])->name('dynamic-pricing.index');
        Route::get('dynamic-pricing-data', [DynamicPricingController::class, 'index_data'])->name('dynamic-pricing.index_data');
        Route::get('dynamic-pricing/{id}', [DynamicPricingController::class, 'show'])->name('dynamic-pricing.show');
        Route::post('dynamic-pricing/update', [DynamicPricingController::class, 'updatePricing'])->name('dynamic-pricing.update');
        Route::post('dynamic-pricing/bulk-update', [DynamicPricingController::class, 'bulkUpdatePricing'])->name('dynamic-pricing.bulk-update');
        Route::get('dynamic-pricing/analytics', [DynamicPricingController::class, 'analytics'])->name('dynamic-pricing.analytics');
        Route::post('dynamic-pricing/price-comparison', [DynamicPricingController::class, 'priceComparison'])->name('dynamic-pricing.price-comparison');
        Route::get('dynamic-pricing/export', [DynamicPricingController::class, 'export'])->name('dynamic-pricing.export');
    });

    // Provider E-commerce Routes
    Route::group(['prefix' => 'provider', 'middleware' => ['role:provider']], function () {
        Route::get('dashboard', [ProviderOrderController::class, 'dashboard'])->name('provider.dashboard');

        // Store Management
        Route::get('store', [ProviderStoreController::class, 'index'])->name('provider.store.index');
        Route::get('store/create', [ProviderStoreController::class, 'create'])->name('provider.store.create');
        Route::post('store', [ProviderStoreController::class, 'store'])->name('provider.store.store');
        Route::get('store/edit', [ProviderStoreController::class, 'edit'])->name('provider.store.edit');
        Route::put('store', [ProviderStoreController::class, 'update'])->name('provider.store.update');

        // Store Products
        Route::get('store/products', [ProviderStoreController::class, 'products'])->name('provider.store.products');
        Route::get('store/products-data', [ProviderStoreController::class, 'products_data'])->name('provider.store.products_data');
        Route::post('store/add-product', [ProviderStoreController::class, 'addProduct'])->name('provider.store.add-product');
        Route::put('store/product/{id}', [ProviderStoreController::class, 'updateProduct'])->name('provider.store.update-product');
        Route::delete('store/product/{id}', [ProviderStoreController::class, 'removeProduct'])->name('provider.store.remove-product');

        // Product Management
        Route::resource('product', ProviderProductController::class, ['as' => 'provider']);
        Route::get('product-index-data', [ProviderProductController::class, 'index_data'])->name('provider.product.index_data');
        Route::get('available-products', [ProviderProductController::class, 'availableProducts'])->name('provider.product.available');

        // Order Management
        Route::get('orders', [ProviderOrderController::class, 'index'])->name('provider.order.index');
        Route::get('orders-data', [ProviderOrderController::class, 'index_data'])->name('provider.order.index_data');
        Route::get('orders/{id}', [ProviderOrderController::class, 'show'])->name('provider.order.show');
        Route::post('order-update-status', [ProviderOrderController::class, 'updateStatus'])->name('provider.order.update-status');
        Route::get('order-statistics', [ProviderOrderController::class, 'statistics'])->name('provider.order.statistics');
    });

});
Route::get('/ajax-list',[HomeController::class, 'getAjaxList'])->name('ajax-list');
Route::post('/service-list',[HomeController::class, 'getAjaxServiceList'])->name('service-list');

// Frontend E-commerce Routes
// Main Store Route (Unified Store)
Route::get('store', [FrontendProductController::class, 'unifiedStore'])->name('store.unified');

// Individual product and store pages
Route::get('product/{slug}', [FrontendProductController::class, 'show'])->name('products.show');
Route::get('store/{slug}', [FrontendProductController::class, 'storeShow'])->name('stores.show');

// Legacy routes (kept for backward compatibility)
Route::get('products', [FrontendProductController::class, 'index'])->name('products.index');
Route::get('products/search', [FrontendProductController::class, 'search'])->name('products.search');
Route::get('products/category/{slug}', [FrontendProductController::class, 'category'])->name('products.category');
Route::get('stores', [FrontendProductController::class, 'stores'])->name('stores.index');

// AJAX endpoints for frontend
Route::get('api/products', [FrontendProductController::class, 'getProducts'])->name('api.products');
Route::get('api/stores', [FrontendProductController::class, 'getStores'])->name('api.stores');

// Authenticated frontend routes
Route::middleware('auth')->group(function () {
    Route::get('cart', [FrontendProductController::class, 'cart'])->name('products.cart');
    Route::get('checkout', [FrontendProductController::class, 'checkout'])->name('products.checkout');
});







