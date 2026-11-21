<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\API\Astrologer\AdsVideoController;
use App\Http\Controllers\API\Astrologer\AdvertisementController;
use App\Http\Controllers\API\Astrologer\AssistantChatController;
use App\Http\Controllers\API\Astrologer\AstroHostController;
use App\Http\Controllers\API\Astrologer\AstrologerAssistantController;
use App\Http\Controllers\API\Astrologer\AstrologerAvailabilityController;
use App\Http\Controllers\API\Astrologer\AstrologerCategoryController;
use App\Http\Controllers\API\Astrologer\AstrologerController;
use App\Http\Controllers\API\Astrologer\AstrologerProductController;
use App\Http\Controllers\API\Astrologer\AstrologerStoryController;
use App\Http\Controllers\API\Astrologer\BlogController;
use App\Http\Controllers\API\Astrologer\LiveAstroController;
use App\Http\Controllers\API\Astrologer\PermissionController;
use App\Http\Controllers\API\Astrologer\SkillController;
use App\Http\Controllers\API\Astrologer\WithDrawController;
use App\Http\Controllers\API\User\AppReviewController;
use App\Http\Controllers\API\User\AstromallProductController;
use App\Http\Controllers\API\Astrologer\CourseController;
use App\Http\Controllers\API\User\BannerController;
use App\Http\Controllers\API\User\CallRequestController;
use App\Http\Controllers\API\User\ChatRequestController;
use App\Http\Controllers\API\User\CommissionController;
use App\Http\Controllers\API\User\CommissionTypeController;
use App\Http\Controllers\API\User\CouponController;
use App\Http\Controllers\API\User\CustomerHomeController;
use App\Http\Controllers\API\User\DailyHoroscopeController;
use App\Http\Controllers\API\User\FollowerController;
use App\Http\Controllers\API\User\GiftController;
use App\Http\Controllers\API\User\HelpSupportController;
use App\Http\Controllers\API\User\HelpSupportQuestionController;
use App\Http\Controllers\API\User\HoroController;
use App\Http\Controllers\API\User\KundaliController;
use App\Http\Controllers\API\User\KundaliMatchingController;
use App\Http\Controllers\API\User\LanguageController;
use App\Http\Controllers\API\User\LiveUserController;
use App\Http\Controllers\API\User\LiveKitController;
use App\Http\Controllers\API\User\MaritalStatusController;
use App\Http\Controllers\API\User\NotificationController;
use App\Http\Controllers\API\User\OrderAddressController;
use App\Http\Controllers\API\User\PaymentController;
use App\Http\Controllers\API\User\ProductCategoryController;
use App\Http\Controllers\API\User\ProductDetailController;
use App\Http\Controllers\API\User\ReportController;
use App\Http\Controllers\API\User\ReportTypeController;
use App\Http\Controllers\API\User\SystemFlagController;
use App\Http\Controllers\API\User\TicketController;
use App\Http\Controllers\API\User\TokenGeneratorController;
use App\Http\Controllers\API\User\UserController;
use App\Http\Controllers\API\Partner\RegistrationController;
use App\Http\Controllers\API\User\UserNotificationController;
Route::post('partner/register', [RegistrationController::class, 'register']);
use App\Http\Controllers\API\User\UserOrderController;
use App\Http\Controllers\API\User\UserReportController;
use App\Http\Controllers\API\User\UserReviewController;
use App\Http\Controllers\API\User\WaitListController;
use Symfony\Component\HttpFoundation\Session\Session;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\User\PujaController;
use App\Http\Controllers\API\User\ExotelController;
use App\Http\Controllers\API\User\ProductRecommendController;
use App\Http\Controllers\API\AiAstrologer\ApiAiAstrologerController;
use App\Http\Controllers\API\AiAstrologer\ApiMasterAiChatBotController;
use App\Http\Controllers\API\Astrologer\PujaController as AstrologerPujaController;
use App\Http\Controllers\ApiChatGPTController;
use App\Http\Controllers\API\User\DefaulterMessageController;
use App\Http\Controllers\API\User\PujaRecommendController;
use App\Http\Controllers\API\User\TrainingVideoController;
use App\Http\Controllers\Admin\SendScheduledNotificationsController;


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

Route::post('/random-call', [CallRequestController::class, 'randomCall']);
Route::post('random-chat', [ChatRequestController::class, 'addRandomChatRequest']);
Route::get('/reset-astrofreepaid', [SendScheduledNotificationsController::class, 'resetAstroFreePaid']);
Route::post('/astrologer-video', [AstrologerController::class, 'AstrologerVideo']);
Route::get('/deleteChatCallRequest', [ChatRequestController::class, 'deleteChatCallRequest'])->name('api.deleteChatCallRequest');

Route::group(['middleware' => 'api'], function () {
    Route::post('login', [UserController::class, 'loginUser']);
    Route::post('user/add', [UserController::class, 'addUser']);
    Route::post('getProfile', [UserController::class, 'getProfile']);
    Route::post('refresh', [UserController::class, 'refreshToken']);
    Route::post('user/update/{id}', [UserController::class, 'updateUser'])->name('user.update');
    Route::post('password/reset', [UserController::class, 'forgotPassword']);
    Route::post('userStatus/update/{id}', [UserController::class, 'activeUser']);
    Route::post('user/delete', [UserController::class, 'deleteUser'])->name('api.deleteUser');
    Route::post('user/updateProfile', [UserController::class, 'updateUserProfile']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::post('validateSession', [AstrologerController::class, 'validateSession']);
    Route::post('validateSessionForAstrologer', [AstrologerController::class, 'validateSessionForAstrologer']);
});
//================================================================ai-astrologer================================================================
Route::get('ai-astrologer-list',[ApiAiAstrologerController::class, 'aiAstrologerList'])->name('ai.astrologer.list');
Route::post('ai-chat-button',[ApiAiAstrologerController::class, 'aiChatButton'])->name('ai.chat.button');
Route::get('ai-chatting-page',[ApiAiAstrologerController::class, 'aiChattingPage'])->name('api.ai.chatting.page');
Route::post('/store-ai-chat-history', [ApiAiAstrologerController::class, 'storeAiChatHistory'])->name('api.store.ai.chat.history');

Route::post('/master-ai-response', [ApiMasterAiChatBotController::class, 'masterAiResponse'])->name('master.ai.response');
Route::get('check-user-balance-api',[ApiMasterAiChatBotController::class, 'checkUserBalanceApi'])->name('check.user.balance.api');
Route::get('master-chat-page-api',[ApiMasterAiChatBotController::class, 'masterChatPageApi'])->name('master.chat.page.api');
Route::post('/store-master-ai-chat-history-api', [ApiMasterAiChatBotController::class, 'storeMasterAiChatHistoryApi'])->name('store.master.ai.chat.history.api');
Route::post('/ask-master', [ApiChatGPTController::class, 'askMaster'])->name('api.ask.master');
Route::post('/ai/generate-image', [ApiChatGPTController::class, 'generateImage'])->name('ai.generate.image');
Route::post('/ai/generate-video', [ApiChatGPTController::class, 'generateVideo'])->name('ai.generate.video');

Route::match(['get', 'post'], '/astrologer/prescriptions', [ApiAiAstrologerController::class, 'getPrescriptions'])->name('api.astrologer.prescriptions');
Route::match(['get', 'post'], '/astrologer/prescriptions/pujas', [ApiAiAstrologerController::class, 'getPrescriptionPujas'])->name('api.astrologer.prescriptions.pujas');
Route::post('/astrologer/prescribe', [ApiAiAstrologerController::class, 'prescribeRemedy'])->name('api.astrologer.prescribe');
Route::match(['get', 'post'], '/astrologer/ai-twin/status', [ApiAiAstrologerController::class, 'getAiTwinStatus'])->name('api.astrologer.ai-twin.status');
Route::post('/astrologer/ai-twin/setup', [ApiAiAstrologerController::class, 'setupAiTwin'])->name('api.astrologer.ai-twin.setup');
Route::post('/ai-astrologer/chat', [ApiMasterAiChatBotController::class, 'masterAiResponse'])->name('api.ai-astrologer.chat');

//================================================================ai-astrologer================================================================

// Get Otl Response
Route::post('getOtlResponse', [AstrologerController::class, 'getOtlResponse']);


// New For Web
Route::post('getUserdetails', [AstrologerController::class, 'getUserdetails']);
Route::post('getAstrologerdetails', [AstrologerController::class, 'getAstrologerdetails']);



Route::post('getUser', [UserController::class, 'getUsers']);
Route::post('getUserById', [AstrologerController::class, 'getUserById']);
Route::post('getUserProfile', [AstrologerController::class, 'getUserProfile']);
//customer
Route::post('loginAppUser', [UserController::class, 'loginAppUser']);
//Device details
Route::post('deviceDetails', [UserController::class, 'addDeviceDetails']);

//------------------------------------------------ADMIN API--------------------------------------------------//
//Skill
Route::post('getSkill', [SkillController::class, 'getAppSkills']);
Route::post('activeSkill', [SkillController::class, 'activeSkills']);

Route::post('getGift', [GiftController::class, 'getGifts']);
Route::post('activeGift', [GiftController::class, 'activeGifts']);
Route::post('sendGift', [GiftController::class, 'sendGifts'])->name('api.sendGifts');

Route::post('getAstrologerCategory', [AstrologerCategoryController::class, 'getAstrologerCategory']);
Route::post('activeAstrologerCategory', [AstrologerCategoryController::class, 'activeAstrologerCategory']);

//Hororscope sign
Route::post('getHororscopeSign', [HoroController::class, 'getHororscopeSign']);
Route::post('activeHororscopeSign', [HoroController::class, 'activeHororscopeSigns']);

//Product category
Route::post('getproductCategory', [ProductCategoryController::class, 'getProductCategory']);
Route::post('getTopProductCategory', [ProductCategoryController::class, 'gettopthreeproductcategory']);
//Coupon code
Route::post('getCouponcode', [CouponController::class, 'getCouponcode']);

//Banner
Route::post('getBanner', [BannerController::class, 'getBanner']);
Route::post('getBannerType', [BannerController::class, 'getBannerType']);
//Notification
Route::post('notification/add', [NotificationController::class, 'addNotification']);
Route::post('notification/update/{id}', [NotificationController::class, 'updateNotification']);
Route::post('getNotification', [NotificationController::class, 'getNotification']);
Route::post('notificationStatus/update/{id}', [NotificationController::class, 'activeNotification']);
Route::post('notification/delete', [NotificationController::class, 'deleteNotification']);
Route::post('notification/send', [NotificationController::class, 'sendNotification']);
Route::post('getActiveUser', [NotificationController::class, 'getUser']);
//User notification
Route::post('userNotification/add', [UserNotificationController::class, 'addUserNotification']);
Route::post('userNotification/update/{id}', [UserNotificationController::class, 'updateUserNotification']);
Route::post('getUserNotification', [UserNotificationController::class, 'getUserNotification'])->name('api.getUserNotification');
Route::post('userNotificationStatus/update/{id}', [UserNotificationController::class, 'activeUserNotification']);
Route::post('userNotification/deleteUserNotification', [NotificationController::class, 'deleteUserNotification']);
Route::post('userNotification/deleteAllNotification', [NotificationController::class, 'deleteAllUserNotification'])->name('api.deleteAllUserNotification');
//Permission
Route::post('permission/add', [PermissionController::class, 'addPermission']);
Route::post('permission/update/{id}', [PermissionController::class, 'updatePermission']);
Route::post('getPermission', [PermissionController::class, 'getPermission']);
Route::post('permissionStatus/update/{id}', [PermissionController::class, 'activePermission']);

//Commission type pending
Route::post('getCommissionType', [CommissionTypeController::class, 'getCommissionType']);

//Commission  pending
Route::post('getCommission', [CommissionController::class, 'getCommission']);

//Help and support
Route::post('getHelpSupport', [HelpSupportController::class, 'getHelpSupport']);

//Help and support questions
Route::post('getHelpSupportQuestion', [HelpSupportQuestionController::class, 'getHelpSupportQuestion']);

Route::post('getHelpSupportSubSubCategory', [HelpSupportQuestionController::class, 'getHelpSupportSubSubCategory']);
//Set Discounted Price
Route::post('astrologer/setDiscountPrice', [AstrologerController::class, 'setDiscountPrice']);

//Astrologer
Route::post('astrologer/add', [AstrologerController::class, 'addAstrologer']);
Route::post('astrologer/update', [AstrologerController::class, 'updateAstrologer']);
Route::post('astrologer/updateRates', [AstrologerController::class, 'updateRates']);
Route::post('astrologer/updateStatus', [AstrologerController::class, 'updateStatus']);
Route::post('astrologer/updateBankDetails', [AstrologerController::class, 'updateBankDetails']);
Route::post('getAstrologer', [AstrologerController::class, 'getAstrologer']);
Route::post('getCounsellor', [AstrologerController::class, 'getCounsellor']);
Route::post('astrologer/delete', [AstrologerController::class, 'deleteAstrologer']);
Route::post('astrologerVerify/update/{id}', [AstrologerController::class, 'verifyAstrologer']);
Route::post('getMasterAstrologer', [AstrologerController::class, 'masterAstrologer']);
Route::post('addAstrologerAvailability', [AstrologerAvailabilityController::class, 'addAstrologerAvailability']);
Route::post('updateAstrologerAvailability', [AstrologerAvailabilityController::class, 'updateAstrologerAvailability']);
Route::post('getAstrologerAvailability', [AstrologerAvailabilityController::class, 'getAstrologerAvailability']);
Route::post('checkContactNoExist', [AstrologerController::class, 'checkContactNoExist']);
Route::post('checkContactNoExistForUser', [AstrologerController::class, 'checkContactNoExistForUser'])->name('api.checkContactAndSendOTP');
Route::post('sendOtpMobileEmail', [AstrologerController::class, 'sendOtpMobileEmail'])->name('api.sendOtpMobileEmail');
Route::post('resendOtp', [AstrologerController::class, 'resendOtp'])->name('api.resendOtp');
Route::post('checkemailContactExist', [AstrologerController::class, 'checkemailContactExist'])->name('api.checkemailContactExist');
Route::post('getAdminAstrologer', [AstrologerController::class, 'getAstrologerForAdmin']);
Route::post('getAstrologerById', [AstrologerController::class, 'getAstrologerById']);
Route::post('searchAstro', [AstrologerController::class, 'searchAstro']);
Route::post('getAstrologerForCustomer', [AstrologerController::class, 'getAstrologerForCustomer']);
Route::post('reportBlockAstrologer', [AstrologerController::class, 'reportblockAstrologer'])->name('api.reportBlockAstrologer');
Route::post('unBlockAstrologer', [AstrologerController::class, 'unblockAstrologer'])->name('api.unblockAstrologer');
Route::post('getBlockAstrologer', [AstrologerController::class, 'getBlockAstrologer']);
Route::post('addStatus', [ChatRequestController::class, 'addChatStatus'])->name('api.addChatStatus');
Route::post('addCallStatus', [CallRequestController::class, 'addCallStatus'])->name('api.addCallStatus');
Route::post('getAstrologerByIdForAdmin', [AstrologerController::class, 'getAstrologerByIdForAdmin']);
//Blog
Route::post('getBlog', [BlogController::class, 'getBlog']);
Route::post('getAppBlog', [BlogController::class, 'getAppBlog']);
Route::post('activeBlogs', [BlogController::class, 'activeBlogs']);
Route::post('addBlogReader', [BlogController::class, 'addBlogReader']);
Route::post('getBlogById', [BlogController::class, 'blogShow']);
//Astromall product
Route::post('getAstromallProduct', [AstromallProductController::class, 'getAstromallProductForApp']);
Route::post('getAstromallProductById', [AstromallProductController::class, 'getAstromallProductById']);
Route::post('searchAstromallProductCategory', [AstromallProductController::class, 'searchInProductCategory']);
//Ads banner

//Ads video
Route::post('getAdsVideo', [AdsVideoController::class, 'getAdsVideo']);

//Astrologer advertisement
Route::post('getAdvertisement', [AdvertisementController::class, 'getAdvertisement']);

//Ticket
Route::post('ticket/add', [TicketController::class, 'addTicket']);
Route::post('ticket/update/{id}', [TicketController::class, 'updateTicket']);
Route::post('getTicket', [TicketController::class, 'getTicket']);
Route::post('ticketStatus/update/{id}', [TicketController::class, 'activeTicket']);
Route::post('ticket/deleteAll', [TicketController::class, 'deleteAllTicket']);
Route::post('ticket/addReview', [TicketController::class, 'addTicketReview']);
Route::post('ticket/getReview', [TicketController::class, 'getTicketReview']);
Route::post('ticket/updateReview', [TicketController::class, 'updateTicketReview']);
Route::post('ticket/closeTicket', [TicketController::class, 'closeTicket']);
Route::post('ticket/delete', [TicketController::class, 'deleteTicket']);
Route::post('ticket/restart', [TicketController::class, 'restartTicket']);
Route::post('ticket/pause', [TicketController::class, 'pauseTicket']);
Route::post('checkOpenTicket', [TicketController::class, 'checkOpenTicket']);
Route::post('updateTicketStatus', [TicketController::class, 'updateTicketStatus']);
//Product details
Route::post('productDetails/add', [ProductDetailController::class, 'addProductDetails']);
Route::post('productDetails/update/{id}', [ProductDetailController::class, 'updateProductDetails']);
Route::post('getProductDetails', [ProductDetailController::class, 'getProductDetails']);
Route::post('productDetailsStatus/update/{id}', [ProductDetailController::class, 'activeProductDetails']);

//------------------------------------------------CUSTOMER API--------------------------------------------------//
// New
Route::post('kundali/addnew', [KundaliController::class, 'addKundaliNew'])->name('api.addKundaliNew');
Route::post('kundli/generate-free-pdf', [\App\Http\Controllers\API\KundliApiController::class, 'generateFreePdf'])->name('api.generateFreePdf');
Route::get('kundli/generate-free-pdf', [\App\Http\Controllers\API\KundliApiController::class, 'generateFreePdf']);
Route::post('kundali/getKundaliReport', [KundaliMatchingController::class, 'getKundaliReport'])->name('api.getKundaliReport');
//Kundali
Route::post('kundali/add', [KundaliController::class, 'addKundali'])->name('api.addKundali');
Route::post('kundali/update/{id}', [KundaliController::class, 'updateKundali']);
Route::post('pdf/price', [KundaliController::class, 'getKundaliPrice']);
Route::post('getkundali', [KundaliController::class, 'getKundalis']);
Route::post('kundali/delete', [KundaliController::class, 'deleteKundali'])->name('api.deleteKundali');
Route::post('kundali/get/{id}', [KundaliController::class, 'getKundali']);
Route::post('kundali/show/{id}', [KundaliController::class, 'kundaliShow']);
Route::post('kundali/removeFromTrackPlanet', [KundaliController::class, 'removeFromTrackPlanet']);
Route::post('kundali/addForTrackPlanet', [KundaliController::class, 'addForTrackPlanet']);
Route::post('kundali/getForTrackPlanet', [KundaliController::class, 'getForTrackPlanet']);
Route::post('get/panchang', [KundaliController::class, 'getPanchang']);
//Order address
Route::post('orderAddress/add', [OrderAddressController::class, 'addOrderAddress'])->name('api.addOrderAddress');
Route::post('orderAddress/update/{id}', [OrderAddressController::class, 'updateOrderAddress']);
Route::post('getOrderAddress', [OrderAddressController::class, 'getOrderAddress']);

//Home screen
Route::post('getCustomerHome', [CustomerHomeController::class, 'getCustomerHome']);
Route::delete('/appointment/delete', [CustomerHomeController::class, 'deleteAppointmentApi']);


//Blog

//Kundali matching
Route::post('KundaliMatching/add', [KundaliMatchingController::class, 'addKundaliMatching']);
Route::post('KundaliMatching/report', [KundaliMatchingController::class, 'getMatchReport']);
Route::post('getKundaliReportById', [KundaliController::class, 'getKundaliReportById'])->name('api.getKundaliReportById');

//Language
Route::post('getLanguage', [LanguageController::class, 'getLanguages']);

//review
Route::post('userReview/add', [UserReviewController::class, 'addUserReview'])->name('api.addUserReview');
Route::post('userReview/update/{id}', [UserReviewController::class, 'updateUserReview']);
Route::post('getUserReview', [UserReviewController::class, 'getUserReview']);
Route::post('userReview/delete/{id}', [UserReviewController::class, 'deleteUserReview']);
Route::post('appReview/get', [AppReviewController::class, 'getAppReview']);
Route::post('appReview/add', [AppReviewController::class, 'addAppReview']);
Route::post('getAstrologerUserReview', [UserReviewController::class, 'getAstrologerUserReview'])->name('api.getAstrologerUserReview');
Route::post('getUserHistoryReview', [UserReviewController::class, 'getUserHistoryReview']);
Route::post('userReview/reply', [UserReviewController::class, 'replyAstrologerReview']);
Route::post('blockUserReview', [UserReviewController::class, 'blockUserReview']);
Route::post('getBlockUserReview', [UserReviewController::class, 'getBlockReview']);
Route::post('getUserReviewForAdmin', [UserReviewController::class, 'getAstrologerUserReviewForAdmin']);

//Follower
Route::post('follower/add', [FollowerController::class, 'addFollowing'])->name('api.addFollowing');
Route::post('follower/update', [FollowerController::class, 'updateFollowing'])->name('api.updateFollowing');
Route::post('getFollower', [FollowerController::class, 'getFollowing']);
Route::post('getAstrologerFollower', [FollowerController::class, 'getFollower']);

//User report
Route::post('userReport/add', [UserReportController::class, 'addReport'])->name('api.addReport');
Route::post('getUserReport', [UserReportController::class, 'getUserReportRequest']);
Route::post('userReport/update/{id}', [UserReportController::class, 'updateUserReport']);
Route::post('getUserReportRequestById/{id?}', [UserReportController::class, 'getUserReportRequestById']);


//Marital status
Route::post('getMaritalStatus', [MaritalStatusController::class, 'getMaritalStatus']);

//Report type
Route::post('getReportType', [ReportTypeController::class, 'getReportTypes']);
//------------------------------------------------ASTROLOGER API--------------------------------------------------//

//Login
Route::post('loginAppAstrologer', [AstrologerController::class, 'loginAstrologer']);

//Astrologer assistant
Route::post('astrologerAssistant/add', [AstrologerAssistantController::class, 'addAstrologerAssistant']);
Route::post('astrologerAssistant/update', [AstrologerAssistantController::class, 'updateAstrologerAssistant']);
Route::post('getAstrologerAssistant', [AstrologerAssistantController::class, 'getAstrologerAssistant']);
Route::post('astrologerAssistant/delete', [AstrologerAssistantController::class, 'deleteAstrologerAssistant']);
Route::post('astrologerAssistant/block', [AstrologerAssistantController::class, 'blockAstrologerAssistant']);
Route::post('astrologerAssistant/unBlock', [AstrologerAssistantController::class, 'unblockAstrologerAssistant']);
//Astrologer available
Route::post('astrologerAvailable/add', [AstrologerAvailabilityController::class, 'addAstrologerAvailable']);

//Astrologer Product
Route::post('astrologerProduct/get', [AstrologerProductController::class, 'getAstrologerForProduct']);

//chatRequest
Route::post('chatcallrequest/deleteauto', [ChatRequestController::class, 'rejectChatCallAutomatic']);
Route::post('chatRequest/add', [ChatRequestController::class, 'addChatRequest'])->name('api.addChatRequest');
Route::post('chatRequest/get', [ChatRequestController::class, 'getChatRequest']);
Route::post('chatRequest/reject', [ChatRequestController::class, 'rejectChatRequest'])->name('api.rejectChatRequest');
Route::post('chatRequest/removeFromWaitList', [ChatRequestController::class, 'removeFromWaitlist']);
Route::post('chatRequest/accept', [ChatRequestController::class, 'acceptChatRequest'])->name('api.acceptChatRequest');
Route::post('chatRequest/storeToken', [ChatRequestController::class, 'storeToken']);
Route::post('chatRequest/storeChatId', [ChatRequestController::class, 'insertChatRequest'])->name('api.insertChatRequest');
Route::post('chatRequest/endChat', [ChatRequestController::class, 'endChatRequest'])->name('api.endChatRequest');
Route::post('chatRequest/acceptChatRequest', [ChatRequestController::class, 'acceptChatRequestFromCustomer'])->name('api.acceptChatRequestFromCustomer');
Route::post('chatRequest/rejectChatRequest', [ChatRequestController::class, 'rejectChatRequestFromCustomer'])->name('api.rejectChatRequestFromCustomer');
Route::post('chatRequest/endLiveChat', [ChatRequestController::class, 'endLiveChatrequest']);
Route::post('chatRequest/addIntakeForm', [ChatRequestController::class, 'intakeForm'])->name('api.intakeForm');
Route::post('chatRequest/getIntakeForm', [ChatRequestController::class, 'getUserIntakForm'])->name('api.getUserIntakForm');
Route::post('checkChatSessionAvailable', [ChatRequestController::class, 'checkChatSessionTaken'])->name('api.checkChatSessionTaken');
Route::post('checkCallSessionAvailable', [ChatRequestController::class, 'checkCallSessionTaken'])->name('api.checkCallSessionTaken');
Route::post('checkFreeSessionAvailable', [ChatRequestController::class, 'checkFreeSessionAvailable']);
Route::post('sessionChat/add', [ChatRequestController::class, 'addSessionChatMessage']);
Route::post('sessionChat/get', [ChatRequestController::class, 'getSessionChatMessages']);

// For toptup
Route::post('updatechatMinute', [ChatRequestController::class, 'updatechatMinute'])->name('api.updatechatMinute');
Route::post('getcurrentDuration', [ChatRequestController::class, 'getcurrentDuration'])->name('api.getcurrentDuration');

// For Call Topup
Route::post('updatecallMinute', [CallRequestController::class, 'updateCallMinute'])->name('api.updatecallMinute');
Route::post('getcurrentCallDuration', [CallRequestController::class, 'getcurrentCallDuration'])->name('api.getcurrentCallDuration');

//callRequest
Route::post('callRequest/add', [CallRequestController::class, 'addCallRequest'])->name('api.addCallRequest');
Route::post('callRequest/get', [CallRequestController::class, 'getCallRequest']);
Route::post('callRequest/reject', [CallRequestController::class, 'rejectCallRequest'])->name('api.rejectCallRequest');
Route::post('callRequest/removeFromWaitList', [CallRequestController::class, 'removeFromWaitlist']);
Route::post('callRequest/accept', [CallRequestController::class, 'acceptCallRequest'])->name('api.acceptCallRequest');
Route::post('callRequest/storeToken', [CallRequestController::class, 'storeToken'])->name('api.storeToken');
Route::post('callRequest/end', [CallRequestController::class, 'endCall'])->name('api.endCall');
Route::post('callRequest/acceptCallRequest', [CallRequestController::class, 'acceptCallRequestFromCustomer'])->name('api.acceptCallRequestFromCustomer');
Route::post('callRequest/rejectCallRequest', [CallRequestController::class, 'rejectCallRequestFromCustomer'])->name('api.rejectCallRequestFromCustomer');
Route::post('storeCallRecording', [CallRequestController::class, 'storeCallRecording']);
Route::post('getCallById', [CallRequestController::class, 'getCallById']);
//userOrder
Route::post('userOrder/add', [UserOrderController::class, 'addUserOrder'])->name('api.addUserOrder');
Route::post('userOrder/cancel', [UserOrderController::class, 'cancelOrder'])->name('api.cancelOrder');
//withdrawlRequest
Route::post('withdrawlrequest/add', [WithDrawController::class, 'sendWithdrawRequest'])->name('api.sendWithdrawRequest');
Route::post('withdrawlrequest/get', [WithDrawController::class, 'getWithdrawRequest']);
Route::post('withdrawlrequest/update', [WithDrawController::class, 'updateWithdrawRequest'])->name('api.updateWithdrawRequest');
Route::post('withdrawlrequest/releaseAmount', [WithDrawController::class, 'releaseAmount']);
Route::post('addpayment', [PaymentController::class, 'addPayment'])->name('user.addpayment');
Route::post('getRechargeAmount', [PaymentController::class, 'getRechargeAmount']);

// withdraw method
Route::post('withdrawlmethod/get', [WithDrawController::class, 'getWithdrawMethod']);
//liveAstro
Route::post('liveAstrologer/add', [LiveAstroController::class, 'addLiveAstrologer'])->name('api.addLiveAstrologer');
Route::post('liveAstrologer/get', [LiveAstroController::class, 'getLiveAstrologer']);
Route::post('liveAstrologer/endSession', [LiveAstroController::class, 'endLiveSession'])->name('api.endLiveSession');
Route::post('liveAstrologer/livechattoken', [LiveAstroController::class, 'addLiveChatToken']);
Route::post('liveAstrologer/getToken', [LiveAstroController::class, 'getToken']);
Route::post('liveAstrologer/getUpcomingAstrologer', [LiveAstroController::class, 'getUpcomingAstrologer']);
Route::post('searchLiveAstro', [LiveAstroController::class, 'searchLiveAstrologer']);
Route::post('/schedule-live', [LiveAstroController::class, 'addLiveSchedule'])->name('scheduleLive.store');
Route::get('/scheduleLive/list', [LiveAstroController::class, 'scheduleList'])->name('scheduleLive.list');
Route::post('/schedule/delete', [LiveAstroController::class, 'scheduleDelete'])->name('api.schedule.delete');


Route::post('userReminder', [LiveAstroController::class, 'userReminder']);
Route::post('/addLiveScheduleweb', [LiveAstroController::class, 'addLiveScheduleweb'])->name('addLiveScheduleweb');
Route::post('liveAstrologerWeb/add', [LiveAstroController::class, 'addLiveAstrologerWeb'])->name('api.addLiveAstrologerWeb');
Route::post('sendNotificationForliveAstro', [LiveAstroController::class, 'sendNotificationForliveAstro'])->name('api.sendNotificationForliveAstro');

//astrohost
Route::post('getAstrohost', [AstroHostController::class, 'getAstrohost']);
Route::post('addAstrohost', [AstroHostController::class, 'addAstrohost']);

//waitlist

Route::post('waitlist/add', [WaitListController::class, 'addWaitList']);
Route::post('waitlist/get', [WaitListController::class, 'getWaitList']);
Route::post('waitlist/delete', [WaitListController::class, 'deleteFromWaitList']);
Route::post('waitlist/updateStatus', [WaitListController::class, 'editWaitList']);

//systemflag
Route::post('getSystemFlag', [SystemFlagController::class, 'getSystemFlag']);
Route::post('getSubCategory', [SystemFlagController::class, 'getSubCategory']);
Route::post('getAppLanguage', [SystemFlagController::class, 'getAppLanguage']);
//report

Route::post('getCallHistoryReport', [ReportController::class, 'getCallHistory']);
Route::post('getChatHistoryReport', [ReportController::class, 'getChatHistory']);
Route::post('partnerWiseEarning', [ReportController::class, 'partnerWiseEarning']);
Route::post('astrologerEarning', [ReportController::class, 'getAstrologerEarning']);
Route::post('orderReport', [ReportController::class, 'orderRequest']);
//liveUser
Route::post('addLiveUser', [LiveUserController::class, 'addLiveUser']);
Route::post('getLiveUser', [LiveUserController::class, 'getLiveUser'])->name('api.getLiveUser');
Route::post('deleteLiveUser', [LiveUserController::class, 'deleteLiveUser']);

//userreprot
Route::post('userreport/add', [UserReportController::class, 'addUserReportFile'])->name('api.addUserReportFile');
Route::post('savetoken', [ChatRequestController::class, 'saveToken']);

//assistantChat
Route::post('getAssistantChatId', [AssistantChatController::class, 'getAssistantChat']);
Route::post('getAssistantChatHistory', [AssistantChatController::class, 'getAssistantChatHistory']);
Route::post('getCustomerPaidSession', [AssistantChatController::class, 'customerPaidSession']);
Route::post('getAssistantChatRequest', [AssistantChatController::class, 'getAssistantChatRequest']);
Route::post('deleteAssistantChat', [AssistantChatController::class, 'deleteAssistantChat']);
//dailyHoroscope
Route::post('getDailyHoroscope', [DailyHoroscopeController::class, 'getDailyHoroscope']);
Route::post('getDailyHoroscopeInsightForAdmin', [DailyHoroscopeController::class, 'getDailyHoroscopeInsightForAdmin']);
Route::post('getHoroscope', [DailyHoroscopeController::class, 'getHoroscope']);
Route::post('generateToken', [TokenGeneratorController::class, 'generateRtmToken']);
Route::post('generateRtcToken', [TokenGeneratorController::class, 'generateRtcToken'])->name('api.generateRtcToken');
Route::post('addHoroscopeFeedback', [DailyHoroscopeController::class, 'addHoroscopeFeedback']);

//livekit (free self-hosted WebRTC used by the v2 apps â€” see docs/new-apps/04 Â§4.2)
Route::post('livekit/token', [LiveKitController::class, 'token'])->name('api.livekit.token');

// pages
Route::post('privacy-policy', [DashboardController::class, 'privacyPolicy'])->name('api.privacyPolicy');
Route::post('tnc', [DashboardController::class, 'termscond'])->name('api.termscond');


// Astrologer Stories
Route::post('addStory', [AstrologerStoryController::class, 'addStory'])->name('api.addStory');
Route::post('getStory', [AstrologerStoryController::class, 'getStory'])->name('api.getStory');
Route::post('deleteStory', [AstrologerStoryController::class, 'deleteStory'])->name('api.deleteStory');

// view Count
Route::post('clickStory', [AstrologerStoryController::class, 'clickStory'])->name('clickStory');
Route::post('getAstrologerStory', [AstrologerStoryController::class, 'getAstrologerStory'])->name('getAstrologerStory');


// Kundali Related Api
Route::post('kundali/basic', [KundaliController::class, 'getBasicKundali'])->name('api.getBasicKundali');
Route::post('kundali/chart', [KundaliController::class, 'getChartKundali'])->name('api.getChartKundali');
Route::post('kundali/astakvarga', [KundaliController::class, 'getAstakvargaKundali'])->name('api.getAstakvargaKundali');
Route::post('kundali/ascendant-report', [KundaliController::class, 'getAscendantKundali'])->name('api.getAscendantKundali');
Route::post('kundali/planet-report', [KundaliController::class, 'getPlanetKundali'])->name('api.getPlanetKundali');
Route::post('kundali/dasha', [KundaliController::class, 'getDashaKundali'])->name('api.getDashaKundali');
Route::post('kundali/dosha', [KundaliController::class, 'getDoshaKundali'])->name('api.getDoshaKundali');

Route::post('getPujaCategory', [PujaController::class, 'getPujaCategory']);
Route::post('getPujaSubCategory', [PujaController::class, 'getPujaSubCategory']);
Route::post('getPujaList', [PujaController::class, 'getPujaList']);
Route::post('getPujaDetails', [PujaController::class, 'getPujaDeatails']);
Route::post('getPujafaq', [PujaController::class, 'getPujafaq']);
Route::post('placedPujaOrder', [PujaController::class, 'placedPujaOrder']);
Route::post('getProfileboost', [PujaController::class, 'getProfileboost']);
Route::post('boostProfile', [PujaController::class, 'boostProfile']);
Route::post('Profileboosthistory', [PujaController::class, 'Profileboosthistory']);


// Exotel Related Api
Route::post('connectCall', [ExotelController::class, 'connectCall']);
Route::post('StatusCallback/{callId?}', [ExotelController::class, 'StatusCallback']);

// Product Recommend
Route::post('addProductRecommend', [ProductRecommendController::class, 'addProductRecommend'])->name("api.addProductRecommend");
Route::post('getProductRecommend', [ProductRecommendController::class, 'getProductRecommend']);

// Puja Recommend
Route::post('addPujaRecommend', [PujaRecommendController::class, 'addPujaRecommend'])->name("api.addPujaRecommend");
Route::post('getPujaRecommend', [PujaRecommendController::class, 'getPujaRecommend']);

Route::post('getPujaRefund', [PujaController::class, 'getPujaRefund'])->name('getPujaRefund');

// Course Related Routes
Route::post('getCourseCategory', [CourseController::class, 'getCourseCategory']);
Route::post('getCourse', [CourseController::class, 'getCourse']);
Route::post('getCourseDetails', [CourseController::class, 'getCourseDetails']);
Route::post('getCourseChapters', [CourseController::class, 'getCourseChapters']);
Route::post('addCourseOrder', [CourseController::class, 'addCourseOrder']);
Route::post('getCourseOrderlist', [CourseController::class, 'getCourseOrderlist']);
Route::post('courseCompletion', [CourseController::class, 'courseCompletion']);

Route::post('checkReferral', [CourseController::class, 'checkReferral']);


Route::post('/store-defaulter-message', [DefaulterMessageController::class, 'storeDefaulterMessage']);
Route::post('getDocumentList', [AstrologerController::class, 'getDocumentList']);

// Astrologer Puja Api
Route::post('astrologerPujaList', [AstrologerPujaController::class, 'astrologerPujaList']);
Route::post('addAstrologerPuja', [AstrologerPujaController::class, 'addAstrologerPuja']);
Route::post('deleteAstrologerPuja', [AstrologerPujaController::class, 'deleteAstrologerPuja']);
Route::post('sendPujatoUser', [AstrologerPujaController::class, 'sendPujatoUser'])->name('api.sendPujatoUser');

// Show Suggested Puja to user
Route::post('suggestedAstrologerPuja', [AstrologerPujaController::class, 'suggestedAstrologerPuja']);
Route::post('deleteSuggestedPuja', [AstrologerPujaController::class, 'deleteSuggestedPuja']);

// Training Video APi
Route::post('getTrainingVideo', [TrainingVideoController::class, 'getTrainingVideo']);


Route::post('faq-list',[DashboardController::class, 'faqList'])->name('api.user.faqList');

// OnlinePuja AI (Powered by Brain & Growth OS failover chain)
Route::post('onlinepuja-ai/chat', [\App\Http\Controllers\API\User\OnlinePujaAiController::class, 'chat'])->name('api.onlinepuja_ai.chat');

// Annadaan & Gau Seva Charity
Route::post('annadaan/donate', [\App\Http\Controllers\API\User\AnnadaanController::class, 'donate'])->name('api.annadaan.donate');

// Growth Engine & Vedic Feature Integration
Route::get('user/gotra-vault', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getGotraVault'])->name('api.user.gotra_vault.get');
Route::post('user/gotra-vault', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'saveGotraVault'])->name('api.user.gotra_vault.save');
Route::get('user/sankalp-vault', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getSankalpVault'])->name('api.user.sankalp_vault');
Route::get('user/checkFirstConsultOffer', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'checkFirstConsultOffer'])->name('api.user.first_consult_offer');
Route::get('panchang/choghadiya', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getChoghadiya'])->name('api.panchang.choghadiya');

// Devotee Consultation & Order History
Route::post('user/consultation-history', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getConsultationHistory'])->name('api.user.consultation_history');
Route::post('getallhistorydetail', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getConsultationHistory']);
Route::post('user/puja-orders', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getUserPujaOrders'])->name('api.user.puja_orders');
Route::post('getUserPujaOrders', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getUserPujaOrders']);
Route::post('user/mall-orders', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getUserOrders'])->name('api.user.mall_orders');
Route::post('getUserOrders', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getUserOrders']);


// 📱 OnlinePuja Manager Pocket Control Center Endpoints
// Spiritual Shorts and Daily AI Reels Feed
// ðŸ“¦ Real-Time Speed Post & Logistics Tracking
Route::get('logistics/track/{awb?}', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'trackAwb'])->name('api.logistics.track');
Route::post('logistics/webhook/awb', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'awbWebhook'])->name('api.logistics.webhook');

Route::get('feed/shorts', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'getSpiritualShorts'])->name('api.feed.shorts');
Route::post('feed/shorts/like', [\App\Http\Controllers\API\User\GrowthEngineController::class, 'likeShort'])->name('api.feed.shorts.like');

Route::prefix('manager')->group(function () {
    // 🔐 Executive Superadmin Authentication Gate
    Route::post('login', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'login']);

    Route::get('radar', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getLiveRadar']);
    Route::post('radar', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getLiveRadar']);
    Route::get('radar/visitors', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getLiveRadarVisitors']);
    Route::get('banners', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getBanners']);
    Route::post('banners', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'saveBanner']);
    Route::post('puja/{id}/price', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'updatePujaPrice']);
    Route::get('sankalps', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getPendingSankalps']);
    Route::post('sankalp/fulfill', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'fulfillSankalp']);
    Route::post('growth/run-action', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'triggerGrowthAction']);
    Route::post('growth/broadcast', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'broadcastPush']);

    // 🤖 Online Puja AI Multi-Agent Pocket Control Center
    Route::get('agents', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'listAgents']);
    Route::post('agents/{id}/toggle', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'toggleAgent']);
    Route::post('agents/{id}/update', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'updateAgentConfig']);
    Route::post('agents/{id}/run-now', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'runAgentNow']);
    Route::get('agents/outputs', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getAgentOutputs']);
    Route::post('agents/outputs/{id}/approve', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'approveAgentOutput']);

    // 🪔 Festivals & Multi-Campaign Parallel Radar
    Route::get('festivals', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getFestivals']);
    Route::post('festivals/schedule', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'scheduleFestival']);

    // 💼 B2B Partner Network & Google Maps Leads & Live Lead Radar
    Route::get('b2b/leads', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getB2bLeads']);
    Route::get('b2b/pitch/{id?}', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getB2bPitch']);
    Route::post('b2b/dispatch', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'dispatchB2bCampaign']);
    Route::post('b2b/radar/scan', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'scanB2bRadar']);
    Route::post('b2b/status', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'updateB2bLeadStatus']);

    // 🚀 Omnichannel Broadcast (WhatsApp + Brevo + Push)
    Route::post('growth/omnichannel-broadcast', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'omnichannelBroadcast']);

    // ðŸ“ Blogs & SEO Publishing Hub
    Route::get('blogs', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getBlogs']);
    Route::post('blogs/{id}/toggle', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'toggleBlog']);
    Route::get('seo/overview', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getVedicSeoOverview']);
    Route::post('seo/aeo-fix', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'fixAeoArticle']);
    Route::post('seo/generate-draft', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'generateVedicDraft']);

    // 📈 Real Keyword Intelligence, Predictive Traffic Forecast & Closed-Loop Goal Achieving
    Route::get('growth/keyword-intel', [\App\Http\Controllers\API\Manager\GrowthIntelligenceController::class, 'getKeywordIntel']);
    Route::get('growth/traffic-forecast', [\App\Http\Controllers\API\Manager\GrowthIntelligenceController::class, 'getForecast']);
    Route::get('growth/goal-status', [\App\Http\Controllers\API\Manager\GrowthIntelligenceController::class, 'getGoalStatus']);
    Route::post('growth/trigger-catchup', [\App\Http\Controllers\API\Manager\GrowthIntelligenceController::class, 'triggerCatchup']);

    // 📱 Social Media Omnichannel Control
    Route::get('social/analytics', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getSocialAnalytics']);
    Route::get('social/posts', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getSocialPosts']);
    Route::post('social/posts/create', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'createSocialPost']);
    Route::post('social/posts/{id}/update', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'updateSocialPost']);
    Route::post('social/posts/{id}/generate-banner', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'generatePostBanner']);
    Route::post('social/posts/{id}/retry', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'retrySocialPost']);
    Route::post('social/posts/{id}/publish', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'publishSocialNow']);
    Route::post('social/{id}/publish', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'publishSocialNow']);
    Route::post('social/generate-reel', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'generateSocialReel']);
    Route::post('social/generate-poster', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'generateSocialPoster']);
    Route::post('social/broadcast-reel', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'generateSocialReel']);
    Route::post('social/synthesize-master-video', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'synthesizeMasterVideo']);
    Route::get('social/channels/{id}/test', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'testSocialChannel']);

    // ðŸ›• Master Bookings & Revenue Ledger
    Route::get('bookings', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getAllBookings']);

    // Contact Management (Devotees & Astrologers)
    Route::get('contacts', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getContacts']);
    Route::get('contacts/{id}', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getContactDetail']);

    // WhatsApp Central Inbox (Evolution API v2)
    Route::get('whatsapp/status', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getWhatsappStatus']);
    Route::get('whatsapp/threads', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getWhatsappThreads']);
    Route::get('whatsapp/messages/{jid}', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getWhatsappMessages'])->where('jid', '.*');
    Route::post('whatsapp/send', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'sendWhatsappMessage']);
    Route::post('whatsapp/toggle-ai', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'toggleWhatsappAi']);
    Route::post('whatsapp/stage', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'updateWhatsappStage']);
    Route::post('whatsapp/webhook', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'handleWhatsappWebhook']);
    Route::get('whatsapp/pair-code', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getWhatsappPairingCode']);
    Route::post('whatsapp/restart', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'restartWhatsappInstance']);
    Route::get('campaigns/whatsapp/qr', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getWhatsappStatus']);
    Route::post('growth/whatsapp-devotee-blast', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'whatsappDevoteeBlast']);
    Route::get('campaigns/b2b/cockpit', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getB2bLeads']);
    Route::post('campaigns/b2b/mark-contacted', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'updateB2bLeadStatus']);
    Route::post('kundli/generate-pdf', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'generateKundliPdf']);

    // Lead Pipeline Management
    Route::get('leads', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getLeads']);
    Route::post('leads', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'createLead']);
    Route::post('leads/{id}/stage', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'updateLeadStage']);
    Route::post('leads/{id}/note', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'addLeadNote']);

    // Marketing Automation Campaigns
    Route::get('campaigns', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getCampaignAutomations']);
    Route::post('campaigns/trigger', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'triggerCampaignAutomation']);

    // 🤖 Paperclip Autonomous AI Squad Proxy (7 AI Agents)
    Route::get('paperclip/companies', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getPaperclipCompanies']);
    Route::get('paperclip/activity', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getPaperclipActivity']);
    Route::get('paperclip/reports', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getPaperclipReports']);
    Route::get('paperclip/overview', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getPaperclipOverview']);
    Route::get('paperclip/agents', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getPaperclipAgents']);
    Route::get('paperclip/issues', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getPaperclipIssues']);
    Route::post('paperclip/dispatch', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'dispatchPaperclipMission']);
    Route::post('paperclip/pause-agent', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'togglePaperclipAgentPause']);
    Route::post('paperclip/unblock-issue', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'unblockPaperclipIssue']);
    Route::get('paperclip/issue-details/{id}', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getPaperclipIssueDetails']);
    Route::get('paperclip/attachment/{id}', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getPaperclipAttachmentContent']);
    Route::post('paperclip/agent-chat', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'agentDirectChat']);
    Route::post('paperclip/sync-deliverable', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'syncPaperclipDeliverable']);

    // 👥 Team & Access Control Management
    Route::post('google-login', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'googleLogin']);
    Route::get('team', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getTeamList']);
    Route::get('team/roles', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'getTeamRoles']);
    Route::post('team/create', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'createTeamMember']);
    Route::post('team/update', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'updateTeamMember']);
    Route::post('team/toggle-status', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'toggleTeamMemberStatus']);
    Route::post('team/delete', [\App\Http\Controllers\API\Manager\ManagerApiController::class, 'deleteTeamMember']);
});


// Autonomous Free GPU Worker API (Kaggle Dual-T4 / Serverless)
Route::prefix('v1/ai-worker')->group(function () {
    Route::get('ping', [\App\Http\Controllers\API\AiWorkerController::class, 'ping']);
    Route::get('jobs', [\App\Http\Controllers\API\AiWorkerController::class, 'jobs']);
    Route::post('upload', [\App\Http\Controllers\API\AiWorkerController::class, 'upload']);
});

// Test / Mock Webhook Endpoints for Social Dispatch verification
Route::match(['get', 'post'], 'dummy-webhook', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'ok' => true,
        'status' => 'success',
        'message' => 'OnlinePuja Test Social Webhook received payload successfully!',
        'received_at' => now()->toIso8601String(),
        'payload_preview' => $request->all(),
    ]);
});

Route::match(['get', 'post'], 'social/webhook', function (\Illuminate\Http\Request $request) {
    $data = $request->json()->all() ?: $request->all();
    if (empty($data)) {
        $raw = json_decode($request->getContent(), true);
        if (is_array($raw)) $data = $raw;
    }

    $title = trim((string)($data['title'] ?? '🕉️ OnlinePuja Divine Growth Update'));
    $text = trim((string)($data['text'] ?? $data['caption'] ?? $data['content'] ?? ''));
    $videoUrl = trim((string)($data['video_url'] ?? ''));
    $imageUrl = trim((string)($data['image_url'] ?? $data['thumbnail_url'] ?? ''));
    $mediaUrl = !empty($videoUrl) ? $videoUrl : (!empty($imageUrl) ? $imageUrl : trim((string)($data['media_url'] ?? '')));
    $mediaType = !empty($videoUrl) ? 'video' : trim((string)($data['media_type'] ?? 'image'));
    $channels = $data['channels'] ?? ['whatsapp', 'instagram', 'feed'];
    if (!is_array($channels)) $channels = [$channels];

    $outputId = null;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('ai_agents') && \Illuminate\Support\Facades\Schema::hasTable('ai_agent_outputs')) {
            $agentId = \Illuminate\Support\Facades\DB::table('ai_agents')
                ->where('agent_key', 'festival')
                ->orWhere('agent_key', 'paperclip_growth')
                ->value('id');

            if (!$agentId) {
                $agentId = \Illuminate\Support\Facades\DB::table('ai_agents')->insertGetId([
                    'agent_key' => 'paperclip_growth',
                    'name' => 'Paperclip Growth AI',
                    'icon' => '🚀',
                    'role_description' => 'Autonomous Marketing & Creative Engine orchestrated via Paperclip AI',
                    'system_prompt' => 'You are the Autonomous Paperclip Growth Agent for OnlinePuja.live',
                    'language' => 'hi',
                    'tone' => 'vedic_devotional',
                    'model_preference' => 'omniroute',
                    'cron_cadence' => '0 * * * *',
                    'mode' => 'autonomous',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $outputId = \Illuminate\Support\Facades\DB::table('ai_agent_outputs')->insertGetId([
                'agent_id' => $agentId,
                'title' => $title,
                'content_payload' => $text,
                'media_type' => $mediaType,
                'media_url' => $mediaUrl,
                'status' => 'approved',
                'published_at' => now(),
                'distribution_channels' => json_encode($channels),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Paperclip social/webhook ingestion notice: ' . $e->getMessage());
    }

    return response()->json([
        'ok' => true,
        'status' => 'published',
        'message' => 'OnlinePuja Growth OS & App Feeds successfully synced with Paperclip!',
        'output_id' => $outputId,
        'media_type' => $mediaType,
        'channels' => $channels,
        'received_at' => now()->toIso8601String(),
    ]);
});

// 🕉️ WhatsApp 2-Way Interactive Bot Webhook (Evolution API)
Route::match(['get', 'post'], 'whatsapp/webhook', [\App\Http\Controllers\API\WhatsAppWebhookController::class, 'handle'])->name('api.whatsapp.webhook');

// 🚀 Astrologer Personal Social Media Growth OS & AI Co-pilot
Route::prefix('partner/social')->group(function () {
    Route::get('status', [\App\Http\Controllers\API\Partner\AstrologerSocialController::class, 'getStatus']);
    Route::post('connect', [\App\Http\Controllers\API\Partner\AstrologerSocialController::class, 'connectPlatform']);
    Route::post('disconnect', [\App\Http\Controllers\API\Partner\AstrologerSocialController::class, 'disconnectPlatform']);
    Route::post('toggle-autopilot', [\App\Http\Controllers\API\Partner\AstrologerSocialController::class, 'toggleAutopilot']);
    Route::get('creatives', [\App\Http\Controllers\API\Partner\AstrologerSocialController::class, 'getCreatives']);
    Route::post('generate-creative', [\App\Http\Controllers\API\Partner\AstrologerSocialController::class, 'generateCreative']);
    Route::post('publish-creative', [\App\Http\Controllers\API\Partner\AstrologerSocialController::class, 'publishCreative']);
});



