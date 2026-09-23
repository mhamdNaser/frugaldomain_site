<?php


use App\Modules\Core\Controllers\AnalyticsController;
use App\Modules\Core\Controllers\ImageController;
use App\Modules\Core\Controllers\SiteContactController;
use App\Modules\Core\Controllers\DashboardController;
use App\Modules\Core\Controllers\DashboardStatisticsController;
use App\Modules\Core\Controllers\SyncMonitorController;
use App\Modules\Core\Controllers\WebhookLogsController;
use App\Modules\Core\Controllers\WebhookSubscriptionsController;
use App\Modules\Core\Controllers\PartnerNotificationController;
use Illuminate\Support\Facades\Route;


Route::post('/convert-image', [ImageController::class, 'convert']);
Route::get('/download-image/{fileName}', [ImageController::class, 'download']);
Route::post('/site/contact-us', [SiteContactController::class, 'store']);

// Visitor tracking is deliberately public: anonymous visitors are precisely
// what these endpoints exist to count. They return 204 and never a body.
Route::post('/track/visit', [AnalyticsController::class, 'trackVisit']);
Route::post('/track/icon', [AnalyticsController::class, 'trackIconEvent']);


Route::prefix('admin')->group(function () {

    Route::middleware(['auth:sanctum', 'role:partner|admin'])->group(function () {
        Route::post('sync-monitor/{type}', [SyncMonitorController::class, 'index']);
        Route::post('allWebhookLogs', [WebhookLogsController::class, 'index']);
        Route::post('allWebhookSubscriptions', [WebhookSubscriptionsController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {
        Route::get('dashboard/partner-statistics', [DashboardStatisticsController::class, 'partner']);
        Route::get('partner/notifications', [PartnerNotificationController::class, 'index']);
        Route::get('partner/notifications/{id}', [PartnerNotificationController::class, 'show']);
        Route::post('partner/notifications/{id}/read', [PartnerNotificationController::class, 'markAsRead']);
        Route::post('partner/notifications/read-all', [PartnerNotificationController::class, 'markAllAsRead']);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('dashboard/admin-statistics', [DashboardStatisticsController::class, 'admin']);

        Route::controller(DashboardController::class)->group(function () {
            Route::get('statistics', 'statistics')->name('admin-statistics');
            Route::get('quick-stats', 'quickStats')->name('quick-stats');

            Route::get('/icon-statistics', 'iconStatistics');
            Route::get('/icon-quick-stats', 'quickIconStats');
        });

        Route::controller(AnalyticsController::class)->prefix('analytics')->group(function () {
            Route::get('overview', 'overview');
            Route::get('icons', 'icons');
            Route::get('logins', 'logins');
            Route::get('active-users', 'activeUsers');
            Route::get('pages', 'pages');
            Route::get('sessions', 'sessions');
            Route::get('sessions/{sessionId}', 'sessionJourney');
            Route::get('longest-stay-pages', 'longestStayPages');
        });
    });
});
