<?php

/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider and all of them will
 * | be assigned to the "web" middleware group. Make something great!
 * |
 */

use App\Http\Controllers\Admin\AdsVideoController;
use App\Http\Controllers\Admin\AppDesignController;
use App\Http\Controllers\Admin\AppFeedbackController;
use App\Http\Controllers\Admin\AstrologerCategoryController;
use App\Http\Controllers\Admin\AstrologerController;
use App\Http\Controllers\Admin\AstrologerDocumentController;
use App\Http\Controllers\Admin\BrainChatController;
use App\Http\Controllers\Admin\GrowthOsController;
use App\Http\Controllers\Admin\AstroMallController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlockAstrologerController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CallHistoryReportController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ChatHistoryReportController;
use App\Http\Controllers\Admin\ColorSchemeController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DailyHoroScopeController;
use App\Http\Controllers\Admin\DailyHoroscopeInsightController;
use App\Http\Controllers\Admin\DarkModeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataMonitorController;
use App\Http\Controllers\Admin\DefaultImageController;
use App\Http\Controllers\Admin\DemoController;
use App\Http\Controllers\Admin\EarningController;
use App\Http\Controllers\Admin\FCMController;
use App\Http\Controllers\Admin\GiftController;
use App\Http\Controllers\Admin\HelpSupportController;
use App\Http\Controllers\Admin\HororScopeSignController;
use App\Http\Controllers\Admin\HoroscopeController;
use App\Http\Controllers\Admin\KundaliReportController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderRequestController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageManagementController;
use App\Http\Controllers\Admin\PartnerWiseEarningController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProfileBoostController;
use App\Http\Controllers\Admin\PujaController;
use App\Http\Controllers\Admin\PujaPackageController;
use App\Http\Controllers\Admin\RechargeController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReportBlockController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReportRequestController;
use App\Http\Controllers\Admin\SendScheduledNotificationsController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\StoryController;
use App\Http\Controllers\Admin\SystemFlagController;
use App\Http\Controllers\Admin\TeamRoleController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TrainingVideoController;
use App\Http\Controllers\Admin\UserReminderController;
use App\Http\Controllers\Admin\WithdrawlController;
use App\Http\Controllers\frontend\AiAstrologer\AiAstroHistoryController;
use App\Http\Controllers\frontend\AiAstrologer\AiAstrologerController;
use App\Http\Controllers\frontend\AiAstrologer\MasterAiChatBotController;
use App\Http\Controllers\ChatGPTController;
use App\Http\Controllers\EmailTemplateController;
use Illuminate\Support\Facades\Route;

$professionTitle = strtolower(getProfessionTitle() ?: 'partner');

Route::get('/no-permission', function () {
    return view('pages.no-permission');
})->name('no.permission');

Route::get('give-permission', [DemoController::class, 'givePermission'])->name('give.permission')->middleware('permission:admin-create');
Route::get('add-admin', [DemoController::class, 'addAdmin'])->name('add.admin')->middleware('permission:admin-create');
Route::get('index-admin', [DemoController::class, 'indexAdmin'])->name('index.admin')->middleware('permission:admin-create');
Route::post('store-admin', [DemoController::class, 'storeAdmin'])->name('store.admin')->middleware('permission:admin-create');
Route::delete('delete-admin/{id}', [DemoController::class, 'deleteAdmin'])->name('delete.admin')->middleware('permission:admin-create');
Route::post('/update-permission/{roleId}', [DemoController::class, 'updatePermissions'])->name('updatePermissions')->middleware('permission:admin-create');

Route::get('/admin/sendnotifictionmyusers', [UserReminderController::class, 'sendNotificationMyUsers']);

// 🧠 MASTER BRAIN — AI command chatbot
Route::middleware(['auth'])->prefix('brain')->group(function () {
    Route::post('chat', [BrainChatController::class, 'ask'])->name('brain.chat');
    Route::get('history', [BrainChatController::class, 'history'])->name('brain.history');
});

// 🧠 GROWTH & AI OS — Command Center & Autonomous Hub
Route::middleware(['auth'])->prefix('growth-os')->group(function () {
    Route::get('/', [GrowthOsController::class, 'dashboard'])->name('growth-os.dashboard');
    Route::get('keywords', [GrowthOsController::class, 'keywords'])->name('growth-os.keywords');
    Route::post('keywords', [GrowthOsController::class, 'storeKeyword'])->name('growth-os.keywords.store');
    Route::get('content', [GrowthOsController::class, 'contentPipeline'])->name('growth-os.content');
    Route::post('content/{id}/status', [GrowthOsController::class, 'updateContentStatus'])->name('growth-os.content.status');
    Route::get('seo', [GrowthOsController::class, 'seoAuditor'])->name('growth-os.seo');
    Route::post('seo/{id}/revert', [GrowthOsController::class, 'revertSeoIssue'])->name('growth-os.seo.revert');
    Route::get('social', [GrowthOsController::class, 'socialScheduler'])->name('growth-os.social');
    Route::get('marketing', [GrowthOsController::class, 'marketingHub'])->name('growth-os.marketing');
    Route::get('marketing-hub', [GrowthOsController::class, 'marketingHub']);
    Route::get('marketing_hub', [GrowthOsController::class, 'marketingHub']);
    Route::get('autopilot', [GrowthOsController::class, 'autopilot'])->name('growth-os.autopilot');
    Route::post('autopilot/save', [GrowthOsController::class, 'saveAutopilotSettings'])->name('growth-os.autopilot.save');
    Route::get('ai', [GrowthOsController::class, 'aiEngine'])->name('growth-os.ai');
    Route::post('save-vault', [GrowthOsController::class, 'saveVault'])->name('growth-os.ai.vault');
    Route::post('action', [GrowthOsController::class, 'runAction'])->name('growth-os.action');
});

// Social Media OAuth Connect Flow (Meta Facebook, Instagram Business, X, Pinterest, YouTube)
Route::middleware(['auth'])->prefix('social')->group(function () {
    Route::get('connect/{platform}', [\App\Http\Controllers\Admin\SocialConnectController::class, 'connect'])->name('social.connect');
    Route::get('callback/{platform}', [\App\Http\Controllers\Admin\SocialConnectController::class, 'callback'])->name('social.callback');
    Route::post('confirm', [\App\Http\Controllers\Admin\SocialConnectController::class, 'confirmAccounts'])->name('social.confirm');
    Route::delete('{id}', [\App\Http\Controllers\Admin\SocialConnectController::class, 'disconnect'])->name('social.disconnect');
});

// Direct Aliases for Growth OS & Marketing Hub (web.php is already prefixed with 'admin' in RouteServiceProvider)
Route::middleware(['auth'])->group(function () {
    Route::get('marketing', [\App\Http\Controllers\Admin\MarketingCockpitController::class, 'index']);
    Route::get('marketing-cockpit', [\App\Http\Controllers\Admin\MarketingCockpitController::class, 'index']);
    Route::get('growth/marketing-hub', [GrowthOsController::class, 'marketingHub']);
    Route::get('growth/marketing_hub', [GrowthOsController::class, 'marketingHub']);
    Route::get('growth/marketing', [GrowthOsController::class, 'marketingHub']);
    Route::get('growth-os/marketing_hub', [GrowthOsController::class, 'marketingHub']);
    Route::get('growth-os/marketing-hub', [GrowthOsController::class, 'marketingHub']);
    Route::get('growth', [GrowthOsController::class, 'dashboard']);
});

Route::get('dark-mode-switcher', [DarkModeController::class, 'switch'])->name('dark-mode-switcher');
Route::get('generate-daily-horscope', [HoroscopeController::class, 'generateDailyHorscope'])->name('generate-daily-horscope');
Route::get('generate-weekly-horscope', [HoroscopeController::class, 'generateWeeklyHorscope'])->name('generate-weekly-horscope');
Route::get('generate-yearly-horscope', [HoroscopeController::class, 'generateYearlyHorscope'])->name('generate-yearly-horscope');
Route::get('CallChatDelete', [ChatController::class, 'CallChatDelete']);

Route::get('/send-user-notifications', [SendScheduledNotificationsController::class, 'sendUserNotifications'])->name('send-user-notifications');

Route::get('color-scheme-switcher/{color_scheme}', [ColorSchemeController::class, 'switch'])->name('color-scheme-switcher');

Route::controller(AuthController::class)->group(function () {
    Route::get('login', 'loginView')->name('login.index');
    Route::post('login', 'login')->name('login');
});



Route::get('get-session/{token}', [SessionController::class, 'storeSession'])->name('get-session');

// Controller

Route::post('addSkillApi', [SkillController::class, 'addSkillApi'])->name('addSkillApi');
Route::post('skillStatusApi', [SkillController::class, 'skillStatusApi'])->name('skillStatusApi');
Route::delete('skills', [SkillController::class, 'deleteSkill'])->name('deleteSkill');
Route::delete('gifts', [GiftController::class, 'deleteGift'])->name('deleteGift');
Route::get($professionTitle . 'Categories', [AstrologerCategoryController::class, 'addAstrolgerCategory'])->name('astrologerCategories');
Route::post($professionTitle . 'CategoryApi', [AstrologerCategoryController::class, 'addAstrolgerCategoryApi'])->name('addAstrolgerCategoryApi');
Route::delete('deleteUser', [CustomerController::class, 'deleteUser'])->name('deleteUser');

// Edit

Route::post('editSkillApi', [SkillController::class, 'editSkillApi'])->name('editSkillApi');
Route::post('editGiftApi', [GiftController::class, 'editGiftApi'])->name('editGiftApi');
Route::post('editBannerApi', [BannerController::class, 'editBannerApi'])->name('editBannerApi');
Route::post('editBlogApi', [BlogController::class, 'editBlogApi'])->name('editBlogApi');
Route::post('editHororScopeSignApi', [HororScopeSignController::class, 'editHororScopeSignApi'])->name('editHororScopeSignApi');
Route::post('edit' . $professionTitle . 'CategoryApi', [AstrologerCategoryController::class, 'editAstrolgerCategoryApi'])->name('editAstrologerCategory');
Route::post('editCouponApi', [CouponController::class, 'editCouponApi'])->name('editCouponApi');
Route::post('editNotificationApi', [NotificationController::class, 'editNotificationApi'])->name('editNotificationApi');
Route::post('editUserApi', [CustomerController::class, 'editUserApi'])->name('editUserApi');
Route::post('editProductApi', [AstroMallController::class, 'editProductApi'])->name('editProductApi');
Route::post('editAstroMallApi', [AstroMallController::class, 'editAstroMallApi'])->name('editAstroMallApi');
Route::post('editAdsVideoApi', [AdsVideoController::class, 'editAdsVideoApi'])->name('editAdsVideoApi');
Route::post('editNewsApi', [NewsController::class, 'editNews'])->name('editNewsApi');
Route::post('verified' . $professionTitle . 'Api', [AstrologerController::class, 'verifiedAstrologerApi'])->name('verifiedAstrologerApi');
Route::post('giftStatusApi', [GiftController::class, 'giftStatusApi'])->name('giftStatusApi');
Route::post('couponStatusApi', [CouponController::class, 'couponStatusApi'])->name('couponStatusApi');
Route::post('bannerStatusApi', [BannerController::class, 'bannerStatusApi'])->name('bannerStatusApi');
Route::post('horoScopeStatusApi', [HororScopeSignController::class, 'horoScopeStatusApi'])->name('horoScopeStatusApi');
Route::post('notifcationStatusApi', [NotificationController::class, 'notifcationStatusApi'])->name('notifcationStatusApi');
Route::post('blogStatusApi', [BlogController::class, 'blogStatusApi'])->name('blogStatusApi');

Route::post('astroMallStatusApi', [AstroMallController::class, 'astroMallStatusApi'])->name('astroMallStatusApi');
Route::post('productStatusApi', [AstroMallController::class, 'productStatusApi'])->name('productStatusApi');
Route::post('videoStatusApi', [AdsVideoController::class, 'videoStatusApi'])->name('videoStatusApi');
Route::post('newsStatusApi', [NewsController::class, 'newsStatusApi'])->name('newsStatusApi');
Route::post('astrologyCategoryStatusApi', [AstrologerCategoryController::class, 'astrologyCategoryStatusApi'])->name('astrologyCategoryStatusApi');
Route::post('addReviewfromAdmin', [ReportBlockController::class, 'addReviewfromAdmin'])->name('addReviewfromAdmin');

Route::get('gifts', [GiftController::class, 'addGift'])->name('addGift');
Route::post('addGiftApi', [GiftController::class, 'addGiftApi'])->name('addGiftApi');
Route::get('report', [ReportController::class, 'addReport'])->name('report');
Route::post('reportStatus', [ReportController::class, 'reportTypeStatusApi'])->name('reportStatusApi');
Route::get('reportTypes/{page}', [ReportController::class, 'setReportpage'])->name('setReportpage');
Route::post('editReport', [ReportController::class, 'editReportApi'])->name('editReportApi');
Route::post('addReportApi', [ReportController::class, 'addReportApi'])->name('addReportApi');
Route::get('horoscopeSigns', [HororScopeSignController::class, 'addHororScopeSign'])->name('addHoroscopeSigns');
Route::post('addHororScopeSignApi', [HororScopeSignController::class, 'addHororScopeSignApi'])->name('addHororScopeSignApi');
Route::get('astroMall', [AstroMallController::class, 'addAstroMall'])->name('addAstroMall');
Route::post('addAstroMallApi', [AstroMallController::class, 'addAstroMallApi'])->name('addAstroMallApi');
Route::get('coupon-list', [CouponController::class, 'addCoupon'])->name('addCoupon');
Route::post('addCouponApi', [CouponController::class, 'addCouponApi'])->name('addCouponApi');
Route::post('addBannerApi', [BannerController::class, 'addBannerApi'])->name('addBannerApi');
Route::get('notifications', [NotificationController::class, 'addNotification'])->name('addNotification');
Route::post('addNotificationApi', [NotificationController::class, 'addNotificationApi'])->name('addNotificationApi');
Route::get('blogs', [BlogController::class, 'addBlog'])->name('addBlog');
Route::post('addBlogApi', [BlogController::class, 'addBlogApi'])->name('addBlogApi');
Route::get('adsVideos', [AdsVideoController::class, 'addAdsVideo'])->name('addAdsVideo');
Route::post('addAdsVideoApi', [AdsVideoController::class, 'addAdsVideoApi'])->name('addAdsVideoApi');
Route::post('addNewsApi', [NewsController::class, 'addNewsApi'])->name('addNewsApi');
Route::post('addProductApi', [AstroMallController::class, 'addProductApi'])->name('addProductApi');
Route::post('addProductDetailApi', [AstroMallController::class, 'addProductDetailApi'])->name('addProductDetailApi');
Route::get('customers/add', [CustomerController::class, 'addUser'])->name('add-customer');
Route::get('dailyHoroscope/add', [DailyHoroScopeController::class, 'redirectAddDailyHoroscope'])->name('add-daily-horoscope');
Route::get('horoscope/add', [HoroscopeController::class, 'redirectAddHoroscope'])->name('add-horoscope');
Route::post('addUserApi', [CustomerController::class, 'addUserApi'])->name('addUserApi');
Route::post('dashboard', [DashboardController::class, 'getDashboard'])->name('getDashboard');
Route::get('tnc', [DashboardController::class, 'termscond'])->name('admin.termscond');
Route::get('privacy-policy', [DashboardController::class, 'privacyPolicy'])->name('admin.privacyPolicy');
Route::get('commissions', [CommissionController::class, 'addCommission'])->name('addCommission');
Route::post('addCommissionApi', [CommissionController::class, 'addCommissionApi'])->name('addCommissionApi');
Route::delete('deleteCommission', [CommissionController::class, 'deleteCommission'])->name('deleteCommission');

// Route::group(['middleware'=>'web'],function(){
Route::group(['middleware' => ['web']], function () use ($professionTitle) {
    // Route::get('dashboard', [DashboardController::class, 'getDashboard'])->name('dashboard');
    //

    Route::get('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('editProfileApi', [AuthController::class, 'editProfileApi'])->name('editProfileApi');
    Route::get('editProfile', [AuthController::class, 'editProfile'])->name('editProfile');
    Route::post('changePassword', [AuthController::class, 'changePassword'])->name('changePassword');
    Route::controller(PageController::class)->group(function () use ($professionTitle) {
        Route::get('dashboard', [DashboardController::class, 'getDashboard'])->name('dashboard');
        Route::get('dashboard-overview-2-page', 'dashboardOverview2')->name('dashboard-overview-2');
        Route::get('dashboard-overview-3-page', 'dashboardOverview3')->name('dashboard-overview-3');
        Route::get('dashboard-overview-4-page', 'dashboardOverview4')->name('dashboard-overview-4');
        Route::get('categories-page', 'categories')->name('categories');
        Route::get('add-product', 'addProduct')->name('pages.add-product');
        Route::get('product-list-page', 'productList')->name('product-list');
        Route::get('product-grid-page', 'productGrid')->name('product-grid');
        Route::get('transaction-list-page', 'transactionList')->name('transaction-list');
        Route::get('transaction-detail-page', 'transactionDetail')->name('transaction-detail');
        Route::get('seller-list-page', 'sellerList')->name('seller-list');
        Route::get('seller-detail-page', 'sellerDetail')->name('seller-detail');
        Route::get('reviews-page', 'reviews')->name('reviews');
        Route::get('inbox-page', 'inbox')->name('inbox');
        Route::get('file-manager-page', 'fileManager')->name('file-manager');
        Route::get('point-of-sale-page', 'pointOfSale')->name('point-of-sale');
        Route::get('chat-page', 'chat')->name('chat-page');
        Route::get('post-page', 'post')->name('post');
        Route::get('calendar-page', 'calendar')->name('calendar');
        Route::get('crud-data-list-page', 'crudDataList')->name('crud-data-list');
        Route::get('crud-form-page', 'crudForm')->name('crud-form');
        Route::get('users-layout-1-page', 'usersLayout1')->name('users-layout-1');
        Route::get('users-layout-2-page', 'usersLayout2')->name('users-layout-2');
        Route::get('users-layout-3-page', 'usersLayout3')->name('users-layout-3');
        Route::get('profile-overview-1-page', 'profileOverview1')->name('profile-overview-1');
        Route::get('profile-overview-2-page', 'profileOverview2')->name('profile-overview-2');
        Route::get('profile-overview-3-page', 'profileOverview3')->name('profile-overview-3');
        Route::get('wizard-layout-1-page', 'wizardLayout1')->name('wizard-layout-1');
        Route::get('wizard-layout-2-page', 'wizardLayout2')->name('wizard-layout-2');
        Route::get('wizard-layout-3-page', 'wizardLayout3')->name('wizard-layout-3');
        Route::get('blog-layout-1-page', 'blogLayout1')->name('blog-layout-1');
        Route::get('blog-layout-2-page', 'blogLayout2')->name('blog-layout-2');
        Route::get('blog-layout-3-page', 'blogLayout3')->name('blog-layout-3');
        Route::get('pricing-layout-1-page', 'pricingLayout1')->name('pricing-layout-1');
        Route::get('pricing-layout-2-page', 'pricingLayout2')->name('pricing-layout-2');
        Route::get('invoice-layout-1-page', 'invoiceLayout1')->name('invoice-layout-1');
        Route::get('invoice-layout-2-page', 'invoiceLayout2')->name('invoice-layout-2');
        Route::get('faq-layout-1-page', 'faqLayout1')->name('faq-layout-1');
        Route::get('faq-layout-2-page', 'faqLayout2')->name('faq-layout-2');
        Route::get('faq-layout-3-page', 'faqLayout3')->name('faq-layout-3');
        Route::get('login-page', 'loginAdmin')->name('loginAdmin');
        Route::get('/', 'loginAdmin')->name('loginAdmin.root');
        Route::get('register-page', [\App\Http\Controllers\frontend\Astrologer\AuthController::class, 'registerPage'])->name('register-page');

        Route::get('add', [DashboardController::class, 'addAstrologer']);
        Route::post('add', [DashboardController::class, 'addAstrologerApi']);

        Route::get('edit/{id}', [DashboardController::class, 'editAstrologer']);
        Route::post('edit', [DashboardController::class, 'editAstrologerApi']);

        Route::post('register', [\App\Http\Controllers\frontend\Astrologer\AuthController::class, 'register'])->name('astrologer.register');
        Route::get('error-page-page', 'errorPage')->name('error-page');
        Route::get('update-profile-page', 'updateProfile')->name('update-profile');
        Route::get('change-password-page', 'changePassword')->name('change-password');
        Route::get('regular-table-page', 'regularTable')->name('regular-table');
        Route::get('tabulator-page', 'tabulator')->name('tabulator');
        Route::get('modal-page', 'modal')->name('modal');
        Route::get('slide-over-page', 'slideOver')->name('slide-over');
        Route::get('notification-page', 'notification')->name('notification');
        Route::get('tab-page', 'tab')->name('tab');
        Route::get('accordion-page', 'accordion')->name('accordion');
        Route::get('button-page', 'button')->name('button');
        Route::get('alert-page', 'alert')->name('alert');
        Route::get('progress-bar-page', 'progressBar')->name('progress-bar');
        Route::get('tooltip-page', 'tooltip')->name('tooltip');
        Route::get('dropdown-page', 'dropdown')->name('dropdown');
        Route::get('typography-page', 'typography')->name('typography');
        Route::get('icon-page', 'icon')->name('icon');
        Route::get('loading-icon-page', 'loadingIcon')->name('loading-icon');
        Route::get('regular-form-page', 'regularForm')->name('regular-form');
        Route::get('datepicker-page', 'datepicker')->name('datepicker');
        Route::get('tom-select-page', 'tomSelect')->name('tom-select');
        Route::get('file-upload-page', 'fileUpload')->name('file-upload');
        Route::get('wysiwyg-editor-classic', 'wysiwygEditorClassic')->name('wysiwyg-editor-classic');
        Route::get('wysiwyg-editor-inline', 'wysiwygEditorInline')->name('wysiwyg-editor-inline');
        Route::get('wysiwyg-editor-balloon', 'wysiwygEditorBalloon')->name('wysiwyg-editor-balloon');
        Route::get('wysiwyg-editor-balloon-block', 'wysiwygEditorBalloonBlock')->name('wysiwyg-editor-balloon-block');
        Route::get('wysiwyg-editor-document', 'wysiwygEditorDocument')->name('wysiwyg-editor-document');
        Route::get('validation-page', 'validation')->name('validation');
        Route::get('chart-page', 'chart')->name('chart');
        Route::get('slider-page', 'slider')->name('slider');
        Route::get('image-zoom-page', 'imageZoom')->name('image-zoom');
        Route::get('add-customer', 'addCustomer')->name('pages.add-customer');
        Route::get('customer-detail', 'customerDetail')->name('pages.customer-detail');
        Route::get($professionTitle . 's', 'astrologerList')->name('pages.astrologers');

        Route::get($professionTitle . '-detail', 'astrologerDetail')->name('pages.astrologer-detail');
        Route::get('add-' . $professionTitle, 'addAstrologer')->name('add-astrologer');
        Route::get('add-skill', 'addSkill')->name('add-skill');
        Route::get('commissions', 'commissionList')->name('pages.commissions');
        Route::get($professionTitle . 'Categories', 'astrologerCategoryList')->name('pages.astrologerCategories');
        Route::get('coupon-list', 'couponList')->name('pages.coupon-list');
        Route::get('banners', 'bannerList')->name('pages.banners');
        Route::get('report', 'report')->name('pages.report');
        Route::get('notifications', 'notificationList')->name('pages.notifications');
        Route::get('user-notification-list', 'userNotificationList')->name('user-notification-list');
        Route::get('permission', 'permission')->name('permission');
        Route::get('commission-type', 'commissionType')->name('commission-type');
        Route::get('help-support', 'helpSupport')->name('help-support');
        Route::get('help-support-queston-answer', 'helpSupportQuestonAnswer')->name('help-support-queston-answer');
        Route::get('setting', 'systemFlag')->name('pages.setting');
        Route::get('blogs', 'blogList')->name('pages.blogs');
        Route::get('helpSupport', 'helpSupport')->name('helpSupport');
        Route::get('add-blog', 'addBlog')->name('add-blog');
        Route::get('blog-detail', 'blogDetail')->name('blog-detail');
        Route::get('astroMall', 'astromall')->name('pages.astroMall');
        Route::get('adsVideos', 'adsVideo')->name('pages.adsVideos');
        Route::get('edit-blog', 'editBlog')->name('edit-blog');
        Route::get('products', 'product')->name('pages.products');
        Route::get('edit-customer', 'editCustomer')->name('pages.edit-customer');
        Route::get('edit-product', 'editProduct')->name('pages.edit-product');
        Route::get('callHistory', 'callHistory')->name('pages.callHistory');
        Route::get('chat', 'chat')->name('pages.chat');
        Route::get('reportBlock', 'reportBlock')->name('reportBlock');
        Route::get('product-detail', 'productDetail')->name('product-detail');
        Route::get('dailyHoroscope', 'dailyHoroscope')->name('pages.dailyHoroscope');
        Route::get('withdrawalRequests', 'withdrawlRequest')->name('pages.withdrawalRequests');
        // Get data Controller
        Route::get('skills', [SkillController::class, 'getSkill'])->name('skills');
        Route::get($professionTitle . 'Review', [ReportBlockController::class, 'getReportBlock'])->name('astrologerReview');
        Route::get('block' . $professionTitle, [BlockAstrologerController::class, 'getBlockAstrologer'])->name('blockAstrologer');
        Route::post('block' . $professionTitle, [BlockAstrologerController::class, 'getBlockAstrologer'])->name('post-blockAstrologer');
        Route::get('gifts', [GiftController::class, 'getGift'])->name('gifts');
        Route::post('gifts', [GiftController::class, 'getGift'])->name('post-gifts');
        Route::get('tickets', [TicketController::class, 'getTicket'])->name('tickets');
        Route::get('astrologer-tickets', [TicketController::class, 'getAstrologerTicket'])->name('astrologerTickets');

        Route::get('horoscopeSigns', [HororScopeSignController::class, 'getHororScopeSign'])->name('horoscopeSigns');
        Route::get('productCategories', [AstroMallController::class, 'getastroMall'])->name('productCategories');
        Route::post('productCategories', [AstroMallController::class, 'getastroMall'])->name('post-productCategories');
        Route::get('coupon-list', [CouponController::class, 'getCoupon'])->name('coupon-list');
        Route::match(['get', 'post'], 'products', [AstroMallController::class, 'getProduct'])->name('products');
        Route::get('banners', [BannerController::class, 'getBanner'])->name('banners');
        Route::get('notifications', [NotificationController::class, 'getNotification'])->name('notifications');
        Route::match(['get', 'post'], 'blogs', [BlogController::class, 'getBlog'])->name('blogs');

        Route::get('pages', [PageManagementController::class, 'getPage'])->name('pages');
        Route::post('pageStatusApi', [PageManagementController::class, 'pageStatusApi'])->name('pageStatusApi');
        Route::delete('deletepage', [PageManagementController::class, 'deletePage'])->name('deletepage');
        Route::post('editpageApi', [PageManagementController::class, 'editPageApi'])->name('editpageApi');
        Route::post('addpageApi', [PageManagementController::class, 'addPageApi'])->name('addpageApi');
        Route::get('editpage/{id}', [PageManagementController::class, 'editPage'])->name('editpage');

        Route::match(['get', 'post'], 'reportTypes', [ReportController::class, 'getReport'])->name('reportTypes');
        Route::get('adsVideos', [AdsVideoController::class, 'getAdsVideo'])->name('adsVideos');
        Route::get('astroguruNews', [NewsController::class, 'getNews'])->name('astroguruNews');
        Route::match(['get', 'post'], $professionTitle . 's', [AstrologerController::class, 'getAstrologer'])->name('astrologers');
        Route::match(['get', 'post'], 'pending-requests', [AstrologerController::class, 'getAstrologerPendingRequest'])->name('pending-requests');
        Route::get('print' . $professionTitle . 'list', [AstrologerController::class, 'printAstrologer'])->name('printastrologerlist');
        Route::get('export' . $professionTitle . 'CSV', [AstrologerController::class, 'exportAstrologer'])->name('exportAstrologerCSV');
        Route::get('products/add', [AstroMallController::class, 'getastroMallCategory'])->name('add-product');
        Route::match(['get', 'post'], 'customers', [CustomerController::class, 'getUser'])->name('customers');
        Route::get('printcustomerlist', [CustomerController::class, 'printCustomerList'])->name('printcustomerlist');
        Route::get('exportcustomerCSV', [CustomerController::class, 'exportCustomerCSV'])->name('exportcustomerCSV');
        Route::get('commissions', [CommissionController::class, 'getCommission'])->name('commissions');
        Route::get('coupon-list/{page}', [CouponController::class, 'setCouponPage'])->name('setCouponPage');
        Route::get('customers/{id}', [CustomerController::class, 'getUserDetailApi'])->name('customer-detail');
        Route::get($professionTitle . 's/{id}', [AstrologerController::class, 'astrologerDetailApi'])->name('astrologer-detail');
        Route::get($professionTitle . '-list/' . $professionTitle . '-detail/{id}', [AstrologerController::class, 'astrologerDetailApi'])->name('astrologer-detail-alt');
        Route::get('customers/edit/{id}', [CustomerController::class, 'editUser'])->name('edit-customer');
        Route::get('products/edit/{id}', [AstroMallController::class, 'editProduct'])->name('edit-product');
        Route::get('blogs/{id}', [BlogController::class, 'getBlogById'])->name('getBlogById');
        Route::get('products/{id}', [AstroMallController::class, 'getCategoryById'])->name('getCategoryById');
        Route::get('editBlog/{id}', [BlogController::class, 'editBlog'])->name('editBlog');
        Route::get($professionTitle . 'Categories', [AstrologerCategoryController::class, 'getAstrolgerCategory'])->name('astrologerCategories');
        Route::match(['get', 'post'], 'withdrawalRequests', [WithdrawlController::class, 'getWithDrawlRequest'])->name('withdrawalRequests');
        Route::get('/download-tds-report', [WithdrawlController::class, 'downloadTDSReportCSV'])->name('downloadTDSReport');
        Route::get('/download-tds-report-pdf', [WithdrawlController::class, 'downloadTDSReportPDF'])->name('downloadTDSReportPDF');

        // Route::post('/download-table-pdf', [App\Http\Controllers\Admin\GlobalReportController::class, 'downloadPDF'])->name('download.table.pdf');

        Route::get('/wallet-history-csv', [WithdrawlController::class, 'downloadwallethistoryCSV'])->name('downloadwallethistorycsv');
        Route::get('/wallet-history-pdf', [WithdrawlController::class, 'downloadwallethistoryPDF'])->name('downloadwallethistorypdf');

        Route::get('withdrawalMethods', [WithdrawlController::class, 'getwithdrawalMethods'])->name('withdrawalMethods');
        Route::post('editwithdrawApi', [WithdrawlController::class, 'editwithdrawApi'])->name('editwithdrawApi');
        Route::post('withdrawStatusApi', [WithdrawlController::class, 'withdrawStatusApi'])->name('withdrawStatusApi');

        Route::match(['get', 'post'], 'walletHistory', [WithdrawlController::class, 'getWalletHistory'])->name('walletHistory');

        Route::post('releaseAmount', [WithdrawlController::class, 'releaseAmount'])->name('releaseAmount');
        Route::post('cancelWithdrawAmount', [WithdrawlController::class, 'cancelWithdrawAmount'])->name('cancelWithdrawAmount');
        Route::get('commissions/{page}', [CommissionController::class, 'setCommissionPage'])->name('setCommissionPage');
        Route::post('editCommissionApi', [CommissionController::class, 'editCommissionApi'])->name('editCommissionApi');
        Route::get('setting', [SystemFlagController::class, 'getSystemFlag'])->name('setting');
        Route::post('editSystemFlag', [SystemFlagController::class, 'editSystemFlag'])->name('editSystemFlag')->middleware('permission:system-flag');
        Route::post('sendNotification', [NotificationController::class, 'sendNotification'])->name('sendNotification');
        Route::get('editNotification/{id}', [NotificationController::class, 'redirectEditNotification'])->name('redirectEditNotification');
        Route::match(['get', 'post'], 'callHistory', [CallHistoryReportController::class, 'getCallHistory'])->name('callHistory');

        Route::match(['get', 'post'], 'kundaliearning', [KundaliReportController::class, 'getKundaliEarnings'])->name('kundaliearning');
        Route::match(['get', 'post'], 'reportrequest', [ReportRequestController::class, 'getReportRequest'])->name('reportrequest');
        Route::get('printPdf', [CallHistoryReportController::class, 'printPdf'])->name('printPdf');
        Route::get('export-csv', [CallHistoryReportController::class, 'exportCSV'])->name('exportCSV');
        Route::get('export-report-csv', [ReportRequestController::class, 'exportCSV'])->name('exportReportCSV');
        Route::get('export-chat-csv', [ChatHistoryReportController::class, 'exportChatCSV'])->name('exportChatCSV');
        Route::get('export-earning-csv', [PartnerWiseEarningController::class, 'exportPartnerWiseCSV'])->name('exportPartnerWiseCSV');
        Route::get('export-' . $professionTitle . '-earning-csv', [EarningController::class, 'exportAstrologerEarningCSV'])->name('exportAstrologerEarningCSV');
        Route::get('export-orderrequest', [OrderRequestController::class, 'exportOrderRequestCSV'])->name('exportOrderRequestCSV');
        Route::match(['get', 'post'], 'chatHistory', [ChatHistoryReportController::class, 'getChatHistory'])->name('chatHistory');
        Route::get('printChatPdf', [ChatHistoryReportController::class, 'printPdf'])->name('printChatPdf');
        Route::get('partnerWiseEarning', [PartnerWiseEarningController::class, 'getPartnerWiseEarning'])->name('partnerWiseEarning');
        Route::get('printPartnerWisePdf', [PartnerWiseEarningController::class, 'printPdf'])->name('printPartnerWisePdf');
        Route::match(['get', 'post'], 'earning-report', [EarningController::class, 'getEarning'])->name('earning-report');
        Route::get('print' . $professionTitle . 'Earning', [EarningController::class, 'printPdf'])->name('printAstrologerEarning');
        Route::match(['get', 'post'], 'orderrequest', [OrderRequestController::class, 'getOrderRequest'])->name('orderrequest');
        Route::match(['get', 'post'], 'orders', [OrderController::class, 'getOrders'])->name('orders');

        Route::post('changeOrder', [OrderController::class, 'changeOrderStatus'])->name('changeOrder');
        Route::get('setOrderRequest/{page}', [OrderRequestController::class, 'setOrderRequestPage'])->name('setOrderRequestPage');
        Route::get('printOrderRequest', [OrderRequestController::class, 'printPdf'])->name('printOrder');
        Route::get('printReportPdf', [ReportRequestController::class, 'printPdf'])->name('printReport');
        Route::post('/save-token', [FCMController::class, 'index'])->name('save-token');
        Route::post('/createChat', [ChatController::class, 'createChat'])->name('createChat');
        Route::get('tickets/chats/{id?}', [ChatController::class, 'getFireStoredata'])->name('chats');
        Route::post('closeTicket', [TicketController::class, 'closeTicket'])->name('closeTicket');
        Route::post('pauseTicket', [TicketController::class, 'pauseTicket'])->name('pauseTicket');
        Route::post('helpSupport/add', [HelpSupportController::class, 'addHelpSupport'])->name('addHelpSupport');
        Route::post('helpSupport/edit', [HelpSupportController::class, 'editHelpSupport'])->name('editHelpSupport');
        Route::get('helpSupport', [HelpSupportController::class, 'getHelpSupport'])->name('helpSupport');
        Route::post('helpSupportSubCategory/add', [HelpSupportController::class, 'addHelpSupportSubCategory'])->name('addHelpSupportSubCategory');
        Route::get('helpSupportsubCategory/{helpSupportId?}', [HelpSupportController::class, 'getHelpSupportSubCategory'])->name('helpSupportsubCategory');
        Route::post('helpsupportsubsubcategory/add', [HelpSupportController::class, 'addHelpSupportSubSubCategory'])->name('addHelpSupportSubSubCategory');
        Route::get('helpSupportsubsubCategory/{helpSupportSubCategoryId?}', [HelpSupportController::class, 'getHelpSupportSubSubCategory'])->name('helpSupportsubsubCategory');
        Route::post('editHelpSupportSubCategory', [HelpSupportController::class, 'editHelpSupportSubCategory'])->name('editHelpSupportSubCategory');
        Route::delete('deleteHelpSupport', [HelpSupportController::class, 'deleteHelpSupport'])->name('deleteHelpSupport');
        Route::delete('deleteSubSupport', [HelpSupportController::class, 'deleteSubSupport'])->name('deleteSubSupport');
        Route::post('editHelpSupportSubSubCategory', [HelpSupportController::class, 'editHelpSupportSubSubCategory'])->name('editHelpSupportSubSubCategory');
        Route::delete('deleteHelpSupportSubSubCategory', [HelpSupportController::class, 'deleteHelpSupportSubSubCategory'])->name('deleteHelpSupportSubSubCategory');
        Route::get('dailyHoroscope', [DailyHoroScopeController::class, 'getDailyHoroscope'])->name('dailyHoroscope');
        Route::post('addDailyHoroscope', [DailyHoroScopeController::class, 'addDailyHoroscope'])->name('addDailyHoroscope');
        Route::get('dailyHoroscope/edit', [DailyHoroScopeController::class, 'redirectEditDailyHoroscope'])->name('redirectEditDailyHoroscope');
        Route::get('horoscope/edit', [HoroscopeController::class, 'redirectEditHoroscope'])->name('redirectEditHoroscope');
        Route::post('editDailyHoroscope', [DailyHoroScopeController::class, 'editDailyHoroscope'])->name('editDailyHoroscope');
        Route::match(['get', 'post'], 'dailyHoroscopeInsight', [DailyHoroscopeInsightController::class, 'getDailyHoroscopeInsight'])->name('dailyHoroscopeInsight');
        Route::get('getDailyHoroScopeInsight/{id}', [DailyHoroscopeInsightController::class, 'filterDailyHoroscopeInsight'])->name('getDailyHoroScopeInsight');
        Route::post('addDailyHoroscopeInsight', [DailyHoroscopeInsightController::class, 'addDailyHoroscopeInsight'])->name('addDailyHoroscopeInsight');
        Route::post('editDailyHoroscopeInsight', [DailyHoroscopeInsightController::class, 'editDailyHoroscopeInsight'])->name('editDailyHoroscopeInsight');
        Route::delete('deleteHoroscopeInsight', [DailyHoroscopeInsightController::class, 'deleteHoroscopeInsight'])->name('deleteHoroscopeInsight');
        Route::delete('deleteHoroscope', [DailyHoroScopeController::class, 'deleteHoroscope'])->name('deleteHoroscope');
        Route::match(['get', 'post'], 'horoscope', [HoroscopeController::class, 'getHoroscope'])->name('horoscope');
        Route::match(['get', 'post'], 'yearlyhoroscope', [HoroscopeController::class, 'getyearlyHoroscope'])->name('yearlyhoroscope');
        Route::post('dailyHoroscope', [DailyHoroScopeController::class, 'getDailyHoroscope'])->name('getDailyHoroscope');
        Route::post('addHoroscope', [HoroscopeController::class, 'addHoroscope'])->name('addHoroscope');
        Route::post('loginApi', [LoginController::class, 'loginApi'])->name('loginApi');
        Route::post('editHoroscope', [HoroscopeController::class, 'editHoroscope'])->name('editHoroscope');
        Route::delete('deleteHoro', [HoroscopeController::class, 'deleteHoroscope'])->name('deleteHoro');
        Route::delete('deleteVideo', [AdsVideoController::class, 'deleteVideo'])->name('deleteVideo');
        Route::delete('deleteNews', [NewsController::class, 'deleteNews'])->name('deleteNews');
        Route::post('verified' . $professionTitle, [DashboardController::class, 'verifiedAstrologer'])->name('verifiedAstrologer');
        Route::get('pagination/fetch_data', [CustomerController::class, 'fetch_data'])->name('fetch_data');
        Route::get('feedback', [AppFeedbackController::class, 'getAppFeedback'])->name('feedback');
        Route::match(['get', 'post'], 'customerProfile', [DefaultImageController::class, 'getDefaultImage'])->name('customerProfile');
        Route::post('addDefaultProfile', [DefaultImageController::class, 'addDefaultImage'])->name('addDefaultProfile');
        Route::post('editCustomerProfile', [DefaultImageController::class, 'updateDefaultImage'])->name('editCustomerProfile');
        Route::post('customerProfileApi', [DefaultImageController::class, 'activeInactiveDefaultProfile'])->name('customerProfileApi');

        Route::get($professionTitle . '/add', [AstrologerController::class, 'addAstrologer'])->name('addAstrologer');
        Route::post('add' . $professionTitle . 'Api', [AstrologerController::class, 'addAstrologerApi'])->name('addAstrologerApi');
        Route::get($professionTitle . 's/edit/{id}', [AstrologerController::class, 'editAstrologer'])->name('edit-astrologer');
        Route::post('edit' . $professionTitle . 'Api', [AstrologerController::class, 'editAstrologerApi'])->name('editAstrologerApi');

        Route::delete('deleteReview', [ReportBlockController::class, 'deleteReview'])->name('deleteReview');
        Route::delete('deleteBlog', [BlogController::class, 'deleteBlog'])->name('deleteBlog');
        Route::delete('deleteRechargeAmount', [RechargeController::class, 'deleteRechargeAmount'])->name('deleteRechargeAmount');
        Route::match(['get', 'post'], 'rechargeAmount', [RechargeController::class, 'getRechargeAmount'])->name('rechargeAmount');
        Route::post('addRechargeAmount', [RechargeController::class, 'addRechargeAmount'])->name('addRechargeAmount');
        Route::post('editRechargeAmount', [RechargeController::class, 'editRechargeAmount'])->name('editRechargeAmount');
        Route::get('contactlist', [AppFeedbackController::class, 'contactList'])->name('contactlist');
        Route::get('/contact/details', [AppFeedbackController::class, 'details'])->name('contact.details');

        Route::match(['get', 'post'], 'horoscopeFeedback', [DailyHoroScopeController::class, 'getHoroscopeFeedback'])->name('horoscopeFeedback');
        Route::match(['get', 'post'], 'teamRole', [TeamRoleController::class, 'getTeamRole'])->name('teamRole');
        Route::delete('deleteRole', [TeamRoleController::class, 'deleteTeamRole'])->name('deleteRole');
        Route::post('addTeamRoleApi', [TeamRoleController::class, 'addTeamRoleApi'])->name('addTeamRoleApi');
        Route::post('editTeamRoleApi', [TeamRoleController::class, 'editTeamRoleApi'])->name('editTeamRoleApi');
        Route::get('teamRole/add', [TeamRoleController::class, 'redirectAddTeamRole'])->name('teamRole/add');
        Route::get('teamRole/edit/{id}', [TeamRoleController::class, 'redirectEditTeamRole'])->name('teamRole/edit/{id}');
        Route::match(['get', 'post'], 'team-list', [TeamRoleController::class, 'getTeamMember'])->name('team-list');
        Route::post('addTeamApi', [TeamRoleController::class, 'addTeamApi'])->name('addTeamApi');
        Route::delete('deleteMember', [TeamRoleController::class, 'deleteTeamMember'])->name('deleteMember');
        Route::post('editTeamMemberApi', [TeamRoleController::class, 'editTeamMemberApi'])->name('editTeamMemberApi');
        // Route::get('teamRole/add', [TeamRoleController::class, ''])->name('teamRole/add');

        Route::post('rechargewallet', [CustomerController::class, 'rechargewallet'])->name('rechargewallet');

        // Story Related
        Route::get('getStory', [StoryController::class, 'getStory'])->name('story-list');
        Route::delete('deleteStory', [StoryController::class, 'deleteStory'])->name('deleteStory');

        // ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        Route::get('ai-counsellors', [AiAstrologerController::class, 'index'])->name('ai.astrologers');
        Route::get('create-ai-counsellor', [AiAstrologerController::class, 'create'])->name('create.ai.astrologer');
        Route::post('store-ai-astrologers', [AiAstrologerController::class, 'store'])->name('store.ai.astrologer');
        Route::post('ai-astrologers-search', [AiAstrologerController::class, 'search'])->name('ai.astrologers.search');
        Route::get('edit-ai-counsellor/{slug}', [AiAstrologerController::class, 'edit'])->name('edit.ai.astrologer');
        Route::post('update-ai-astrologers/{id}', [AiAstrologerController::class, 'update'])->name('update.ai.astrologer');
        Route::delete('delete-ai-astrologers/{id}', [AiAstrologerController::class, 'destroy'])->name('delete.ai.astrologer');
        Route::post('ai-chat-page', [AiAstrologerController::class, 'aiChatButton'])->name('ai.chat.page');
        Route::get('ai-chatting-page', [AiAstrologerController::class, 'aiChattingPage'])->name('ai.chatting.page');

        Route::get('ai-chat-list', [AiAstrologerController::class, 'aiAstrologerList'])->name('ai.chat.list');
        Route::post('/ask-chatgpt', [ChatGPTController::class, 'ask'])->name('ask.chatgpt');
        Route::post('/ask-master', [ChatGPTController::class, 'askMaster'])->name('ask.master');

        Route::post('/store-ai-chat-history', [AiAstrologerController::class, 'storeAiChatHistory'])->name('store.ai.chat.history');

        // Route::post('/ai-chat-bot', [AiAstrologerController::class, 'aiChatBot'])->name('ai.chat.bot');
        Route::get('check-user-balance', [MasterAiChatBotController::class, 'checkUserBalance'])->name('check.user.balance');
        Route::get('master-chat-page', [MasterAiChatBotController::class, 'masterChatPage'])->name('master.chat.page');

        Route::get('master-ai-chat-bot', [MasterAiChatBotController::class, 'index'])->name('ai.chat.bot');
        Route::get('create-master-ai-chat-bot', [MasterAiChatBotController::class, 'create'])->name('create.ai.chat.bot');
        Route::post('store-master-ai-chat-bot', [MasterAiChatBotController::class, 'store'])->name('store.ai.chat.bot');
        Route::get('edit-master-ai-chat-bot/{slug}', [MasterAiChatBotController::class, 'edit'])->name('edit.ai.chat.bot');
        Route::post('update-master-ai-chat-bot/{id}', [MasterAiChatBotController::class, 'update'])->name('update.ai.chat.bot');
        Route::delete('delete-master-ai-chat-bot/{id}', [MasterAiChatBotController::class, 'destroy'])->name('delete.ai.chat.bot');
        Route::post('/store-master-ai-chat-history', [MasterAiChatBotController::class, 'storeMasterAiChatHistory'])->name('store.master.ai.chat.history');

        // ----------------------------------------------------------------history-ai-chat----------------------------------------------------------------
        Route::get('export-ai-chat-csv', [AiAstroHistoryController::class, 'exportAiChatCSV'])->name('export.ai.chat.CSV');
        Route::match(['get', 'post'], 'ai-chat-history', [AiAstroHistoryController::class, 'getAiChatHistory'])->name('ai.astrologers.chat.history');
        Route::get('print-ai-chat-Pdf', [AiAstroHistoryController::class, 'printAiPdf'])->name('print.ai.chat.Pdf');
    });
    Route::get('/order/invoice/{id}', [OrderController::class, 'downloadInvoice'])->name('order.invoice');

    Route::post('/update-section-status', [AstrologerController::class, 'updateSectionStatus'])->name('updateSections');

    Route::get('puja-categories', [AstrologerCategoryController::class, 'pujaCategoryList'])->name('puja-categories-list');
    Route::post('add-puja-category', [AstrologerCategoryController::class, 'addPujaCategory'])->name('addPujaCategory');
    Route::post('edit-puja-category', [AstrologerCategoryController::class, 'editPujaCategory'])->name('editPujaCategory');
    Route::post('puja-category-status', [AstrologerCategoryController::class, 'PujaCategoryStatus'])->name('PujaCategoryStatus');

    Route::get('puja-subcategories', [AstrologerCategoryController::class, 'pujaSubCategories'])->name('puja-subcategories-list');
    Route::post('add-puja-subcategory', [AstrologerCategoryController::class, 'addPujaSubCategory'])->name('addPujaSubCategory');
    Route::post('edit-puja-subcategory', [AstrologerCategoryController::class, 'editPujaSubCategory'])->name('editPujaSubCategory');
    Route::post('puja-subcategory-status', [AstrologerCategoryController::class, 'PujaSubCategoryStatus'])->name('PujaSubCategoryStatus');

    Route::get('puja-recommend', [PujaController::class, 'pujaRecommend'])->name('pujaRecommend');

    // Astrologer puja list
    Route::match(['get', 'post'], $professionTitle . '/puja-list', [PujaController::class, 'getAstrologerPuja'])->name('astrologer-puja-list');
    Route::post('adminPujaApproveStatus', [PujaController::class, 'adminPujaApproveStatus'])->name('adminPujaApproveStatus');

    Route::get('puja-package/add', [PujaPackageController::class, 'addpujapackage'])->name('add-pujapackage');
    Route::get('puja/view/{id}', [PujaController::class, 'viewpuja'])->name('view-puja');
    Route::post('/puja-package/store', [PujaPackageController::class, 'store'])->name('puja-package.store');
    Route::get('puja-package', [PujaPackageController::class, 'getpujapackage'])->name('package-list');
    Route::delete('deletePujaPackage', [PujaPackageController::class, 'deletePujaPackage'])->name('deletePujaPackage');
    Route::post('puja-package/update/{id}', [PujaPackageController::class, 'update'])->name('puja-package.update');
    Route::get('puja-package/edit/{id}', [PujaPackageController::class, 'editpackage'])->name('edit-pujapackage');
    Route::post('PackageStatus', [PujaPackageController::class, 'PackageStatus'])->name('PackageStatus');
    Route::get('puja-faq', [PujaPackageController::class, 'pujaFaqList'])->name('puja-faq-list');
    Route::post('add-puja-faq', [PujaPackageController::class, 'addPujaFaq'])->name('addPujaFaq');
    Route::post('edit-puja-faq', [PujaPackageController::class, 'editPujaFaq'])->name('editPujaFaq');
    Route::delete('deletePujaFaq', [PujaPackageController::class, 'deletePujaFaq'])->name('deletePujaFaq');

    Route::get('puja/add', [PujaController::class, 'AddPuja'])->name('add-puja');
    Route::post('puja/store', [PujaController::class, 'store'])->name('puja.store');
    Route::get('puja-list', [PujaController::class, 'getPujaList'])->name('puja-list');
    Route::post('PujaStatus', [PujaController::class, 'PujaStatus'])->name('PujaStatus');
    Route::delete('deletePuja', [PujaController::class, 'deletePuja'])->name('deletePuja');
    Route::get('puja/edit/{id}', [PujaController::class, 'editpuja'])->name('edit-puja');
    Route::post('puja/update/{id}', [PujaController::class, 'update'])->name('puja.update');

    Route::get('puja-order-list', [PujaController::class, 'getPujaOrderList'])->name('puja-order-list');
    Route::post('/update-puja-order', [PujaController::class, 'PujaOrderupdate'])->name('puja-order.update');

    Route::match(['get', 'post'], 'exotelReport', [ReportController::class, 'exotelReport'])->name('exotel-report-list');

    // Course Related Routes
    Route::get('course-categories', [CourseController::class, 'courseCategoryList'])->name('course-categories-list');
    Route::post('add-course-category', [CourseController::class, 'addCourseCategory'])->name('addCourseCategory');
    Route::post('edit-course-category', [CourseController::class, 'editCourseCategory'])->name('editCourseCategory');
    Route::post('course-category-status', [CourseController::class, 'CourseCategoryStatus'])->name('CourseCategoryStatus');

    Route::get('course', [CourseController::class, 'CourseList'])->name('CourseList-list');
    Route::post('add-course', [CourseController::class, 'addCourse'])->name('addCourse');
    Route::post('edit-course', [CourseController::class, 'editCourse'])->name('editCourse');
    Route::post('course-status', [CourseController::class, 'CourseStatus'])->name('CourseStatus');
    Route::delete('delete-course', [CourseController::class, 'deleteCourse'])->name('deleteCourse');

    Route::get('course-chapter', [CourseController::class, 'CourseChapterList'])->name('course-chapter-list');
    Route::get('course-chapter/edit/{id}', [CourseController::class, 'editCourseChapter'])->name('edit-CourseChapter');
    Route::get('add-course-chapter', [CourseController::class, 'viewCourseChapter'])->name('viewCourseChapter');
    Route::post('add-course-chapter', [CourseController::class, 'addCourseChapter'])->name('addCourseChapter');
    Route::post('course-chapter/update/{id}', [CourseController::class, 'updateCourseChapter'])->name('updateCourseChapter');
    Route::post('edit-course-chapter', [CourseController::class, 'editCourseChapter'])->name('editCourseChapter');
    Route::post('course-chapter-status', [CourseController::class, 'CourseChapterStatus'])->name('CourseChapterStatus');
    Route::delete('delete-course-chapter', [CourseController::class, 'deleteCourseChapter'])->name('deleteCourseChapter');
    Route::get('course-orders', [CourseController::class, 'courseOrderList'])->name('courseOrderList');
    Route::post('editTotalOrder', [AstrologerController::class, 'editTotalOrder'])->name('editTotalOrder');

    Route::get('profile-boost', [ProfileBoostController::class, 'addProfileBoost'])->name('profile-boost');
    Route::post('profile/store', [ProfileBoostController::class, 'store'])->name('profile.store');
    Route::get('profile-list', [ProfileBoostController::class, 'getProfileList'])->name('profile-list');

    Route::get('profile-boost/edit/{id}', [ProfileBoostController::class, 'editprofileboost'])->name('profile-boost.edit');
    Route::post('profile-boost/update/{id}', [ProfileBoostController::class, 'update'])->name('profile-boost.update');

    Route::get('kundali-prices', [KundaliReportController::class, 'kundaliPrices'])->name('kundali-prices');
    Route::post('editkundaliAmount', [KundaliReportController::class, 'editkundaliAmount'])->name('editkundaliAmount');

    Route::get('referral-settings', [ReferralController::class, 'editReferral'])->name('referral-settings');
    Route::post('update-referral-settings', [ReferralController::class, 'updateReferral'])->name('update-referral-settings');

    Route::get('userchats', [ChatController::class, 'Userchats'])->name('user-chats');
    Route::get('userchats/{id?}', [ChatController::class, 'getUserChatdata'])->name('userviewchat')->middleware('permission:data-monitoring');

    Route::get('chat-monitoring', [DataMonitorController::class, 'chatsMonitoring'])->name('chats.monitoring');
    Route::get('chat-monitoring/{id?}', [DataMonitorController::class, 'dataMonitoringUser'])->name('data.monitoring.user')->middleware('permission:data-monitoring');
    Route::get('user-chat-monitoring/{id?}', [DataMonitorController::class, 'userDataMonitoringId'])->name('user.data.monitoring.id')->middleware('permission:data-monitoring');
    Route::get('calls-monitoring', [DataMonitorController::class, 'callsMonitoring'])->name('calls.monitoring');

    // Route::get('chat-monitoring-defaulter/{id?}', [DataMonitorController::class, 'dataMonitoringDefaulter'])->name('data.monitoring.defaulter')->middleware('permission:data-monitoring');

    Route::get('block-keywords', [DataMonitorController::class, 'blockKeywords'])->name('block.keywords');
    Route::get('create-keywords', [DataMonitorController::class, 'createKeywords'])->name('create.keywords');
    Route::post('store-keyword', [DataMonitorController::class, 'storeKeyword'])->name('store.keyword');
    Route::get('keyword-edit/{id}', [DataMonitorController::class, 'editKeyword'])->name('edit.keyword');
    Route::post('keyword-update/{id}', [DataMonitorController::class, 'updateKeyword'])->name('update.keyword');
    Route::delete('keyword-delete/{id}', [DataMonitorController::class, 'deleteKeyword'])->name('delete.keyword');

    Route::get('profile-boost-history', [ProfileBoostController::class, 'profileBoostHistory'])->name('profileBoostHistory');
    Route::get('product-reccomend', [OrderController::class, 'productRecommend'])->name('productRecommend');

    Route::get('assistants', [AstrologerController::class, 'astrologerAssistant'])->name('astrologerAssistant');
    Route::delete('deleteassistant', [AstrologerController::class, 'deleteassistant'])->name('deleteassistant');

    // Admin Earning
    Route::match(['get', 'post'], 'adminEarnings', [EarningController::class, 'adminEarnings'])->name('adminEarnings')->middleware('web');
    Route::get('printAdminEarnings', [EarningController::class, 'printAdminEarnings'])->name('printAdminEarnings');
    Route::get('exportAdminEarningCSV', [EarningController::class, 'exportAdminEarningCSV'])->name('exportAdminEarningCSV');

    // Partner Earnings
    Route::get($professionTitle . '-earning', [EarningController::class, 'astrologerEarning'])->name('astrologerEarning');

    // Astrologer Document
    Route::get('document', [AstrologerDocumentController::class, 'document'])->name('document');
    Route::post('addDocument', [AstrologerDocumentController::class, 'addDocument'])->name('addDocument');
    Route::post('editDocument', [AstrologerDocumentController::class, 'editDocument'])->name('editDocument');
    Route::delete('deleteDocument', [AstrologerDocumentController::class, 'deleteDocument'])->name('deleteDocument');

    // Website Home Faq

    Route::get('web-home-faq', [BlogController::class, 'webFaqList'])->name('web-faq-list');
    Route::post('add-web-faq', [BlogController::class, 'addWebFaq'])->name('addWebFaq');
    Route::post('edit-web-faq', [BlogController::class, 'editWebFaq'])->name('editWebFaq');
    Route::delete('deleteWebFaq', [BlogController::class, 'deleteWebFaq'])->name('deleteWebFaq');

    // Email Template
    Route::get('email-template', [EmailTemplateController::class, 'getEmailTemplate'])->name('getEmailTemplate');
    Route::post('add-email-template', [EmailTemplateController::class, 'addEmailTemplate'])->name('addEmailTemplate');
    Route::post('edit-email-template', [EmailTemplateController::class, 'editEmailTemplate'])->name('editEmailTemplate');

    Route::get('app-design', [AppDesignController::class, 'getAppdesign'])->name('getAppdesign');
    Route::post('app-design-status', [AppDesignController::class, 'appDesignStatus'])->name('appDesignStatus');

    // Training Video From Admin
    Route::get('training-videos', [TrainingVideoController::class, 'getTrainingVideo'])->name('getTrainingVideo');
    Route::post('training-videos', [TrainingVideoController::class, 'addTrainingVideo'])->name('addTrainingVideo');
    Route::post('edit-training-video', [TrainingVideoController::class, 'editTrainingVideo'])->name('editTrainingVideo');
    Route::post('status-training-video', [TrainingVideoController::class, 'statusTrainingVideo'])->name('statusTrainingVideo');
    Route::delete('delete-training-video', [TrainingVideoController::class, 'deleteTrainingVideo'])->name('deleteTrainingVideo');

    Route::get('admin-get-tds-comm', [WithdrawlController::class, 'getTdsGst'])->name('admin-get-tds-comm');
    Route::get('/admin/tds-gst/status/{id}/{action}', [WithdrawlController::class, 'changeStatus'])->name('tds-gst.status');
    Route::post('/admin/tds-gst/reject', [WithdrawlController::class, 'reject'])->name('tds-gst.reject');
    Route::get('/admin/tds-gst', [WithdrawlController::class, 'getTdsGst'])->name('tds-gst');
});
