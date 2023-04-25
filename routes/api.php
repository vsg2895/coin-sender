<?php

use App\Http\Controllers\{
    TopController,
    TagController,
    AuthController,
    TaskController,
    RoleController,
    SkillController,
    EventController,
    MyTeamController,
    AccessController,
    ReportController,
    TwitterController,
    ProfileController,
    ManagerController,
    CountryController,
    ProjectController,
    CoinTypeController,
    LanguageController,
    ActivityController,
    DashboardController,
    AutomationController,
    InvitationController,
    PermissionController,
    AmbassadorController,
    SocialLinkController,
    BlockchainController,
    ContactFormController,
    LeaderboardController,
    PopularTaskController,
    NotificationController,
    PendingClaimController,
    ProjectDiscordController,
    AmbassadorTaskController,
    ProjectTelegramController,
    ReferralProgramController,
    AmbassadorWalletController,
    AmbassadorActivityController,
    SocialAuthenticationController,
    ProjectSocialAuthenticationController,
    AmbassadorWalletWithdrawalRequestController,
};

use Illuminate\Support\Facades\Route;

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

Route::group(['middleware' => ['api', 'json.response']], function () {
    Route::get('/', function () {
        return response()->json([
            'manager' => 'api',
        ]);
    });

    Route::get('tags', [TagController::class, 'index']);
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('skills', [SkillController::class, 'index']);
    Route::get('countries', [CountryController::class, 'index']);
    Route::get('languages', [LanguageController::class, 'index']);
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::get('blockchains', [BlockchainController::class, 'index']);
    Route::get('social-links', [SocialLinkController::class, 'index']);
    Route::post('contact-form', [ContactFormController::class, 'store']);

    Route::prefix('invitations')->group(function () {
        Route::get('/verify/{invitation:token}', [InvitationController::class, 'verify']);
        Route::get('/accept/{invitation:token}', [InvitationController::class, 'accept']);
    });

    Route::middleware(['auth', 'any.roles'])->group(function () {
        Route::get('pending-claims', [PendingClaimController::class, 'index']);
        Route::get('referral-program', [ReferralProgramController::class, 'index']);

        Route::prefix('top')->group(function () {
            Route::get('/talents', [TopController::class, 'talents']);
            Route::get('/reviewers', [TopController::class, 'reviewers']);
        });

        Route::prefix('events')->group(function () {
            Route::get('/', [EventController::class, 'index']);
            Route::post('/', [EventController::class, 'store']);
        });

        Route::prefix('reports')->group(function () {
            Route::get('/', [ReportController::class, 'index']);
            Route::get('/projects', [ReportController::class, 'projects']);
        });

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/read', [NotificationController::class, 'read']);
        });

        Route::prefix('profile')->group(function () {
            Route::put('update', [ProfileController::class, 'update']);
            Route::post('update-avatar', [ProfileController::class, 'updateAvatar']);
            Route::delete('delete-avatar', [ProfileController::class, 'deleteAvatar']);
        });

        Route::get('tasks/popular', [PopularTaskController::class, 'index']);
        Route::post('tasks/estimated-amount', [TaskController::class, 'calculateEstimatedAmount']);
        Route::apiResource('tasks', TaskController::class);

        Route::prefix('ambassadors/tasks')->group(function () {
            Route::get('/', [AmbassadorTaskController::class, 'index']);
            Route::get('/{ambassadorTask}', [AmbassadorTaskController::class, 'show']);
            Route::post('done/{ambassadorTask}', [AmbassadorTaskController::class, 'done']);
            Route::post('reject/{ambassadorTask}', [AmbassadorTaskController::class, 'reject']);
            Route::post('return/{ambassadorTask}', [AmbassadorTaskController::class, 'return']);
            Route::post('take-on-revision/{ambassadorTask}', [AmbassadorTaskController::class, 'takeOnRevision']);
        });

        Route::prefix('twitter')->group(function () {
            Route::get('user/{name}', [TwitterController::class, 'user']);
            Route::get('tweet/{id}', [TwitterController::class, 'tweet']);
            Route::get('space/{name}', [TwitterController::class, 'space']);
        });

        Route::post('automations/connect-telegram', [AutomationController::class, 'connectTelegram']);

        Route::prefix('ambassadors')->group(function () {
            Route::get('autocomplete', [AmbassadorController::class, 'autocomplete']);
            Route::post('invite/{ambassador}', [AmbassadorController::class, 'invite']);
            Route::post('level-up/{ambassador}', [AmbassadorController::class, 'levelUp']);
        });

        Route::prefix('projects')->group(function () {
            Route::get('/{project}/activities', [ProjectController::class, 'activities']);
            Route::get('/{project}/pending-reviews', [ProjectController::class, 'pendingReviews']);

            Route::get('/{project}/discord/guild', [ProjectDiscordController::class, 'index']);
            Route::put('/{project}/discord/guild', [ProjectDiscordController::class, 'update']);
            Route::get('/{project}/discord/guild/roles', [ProjectDiscordController::class, 'roles']);
            Route::get('/{project}/social-providers', [ProjectController::class, 'providers']);

            Route::get('/{project}/telegram/group', [ProjectTelegramController::class, 'index']);
            Route::put('/{project}/telegram/group', [ProjectTelegramController::class, 'update']);

            Route::get('/auth/{provider}/redirect', [ProjectSocialAuthenticationController::class, 'redirectProvider'])
                ->where('provider', 'twitter|discord_bot');

            Route::get('/auth/{provider}/callback', [ProjectSocialAuthenticationController::class, 'handleProviderCallback'])
                ->where('provider', 'twitter|discord_bot');

            Route::delete('/{project}/auth/{provider}', [ProjectSocialAuthenticationController::class, 'destroy'])
                ->where('provider', 'twitter|discord_bot|telegram_bot');

            Route::post('validate-name', [ProjectController::class, 'validateName']);
            Route::post('validate-email', [ProjectController::class, 'validateEmail']);
        });

        Route::apiResource('projects', ProjectController::class)->except(['show']);
        Route::get('projects/{invitationProject}', [ProjectController::class, 'show']);

        Route::apiResource('coin-types', CoinTypeController::class);
        Route::apiResource('activities', ActivityController::class)->except('destroy');
        Route::apiResource('ambassadors', AmbassadorController::class)->only(['index', 'show', 'destroy'])->parameters([
            'ambassadors' => 'ambassador',
        ]);

        Route::prefix('ambassadors/wallets')->group(function () {
            Route::get('/withdrawal-requests', [AmbassadorWalletWithdrawalRequestController::class, 'index']);
            Route::get('/{ambassador}', [AmbassadorWalletController::class, 'index']);
            Route::get('/{ambassador}/history', [AmbassadorWalletController::class, 'history']);
            Route::get('/{ambassador}/withdrawal-requests', [AmbassadorWalletController::class, 'withdrawalRequests']);
        });

        Route::prefix('activities')->group(function () {
            Route::delete('/{ambassadorActivity}', [AmbassadorActivityController::class, 'destroy']);
            Route::post('approve/{ambassadorActivity}', [AmbassadorActivityController::class, 'approve']);
            Route::post('decline/{ambassadorActivity}', [AmbassadorActivityController::class, 'decline']);
        });

        Route::prefix('withdrawal-requests')->group(function () {
            Route::post('accept/{walletWithdrawalRequest}', [AmbassadorWalletWithdrawalRequestController::class, 'accept']);
            Route::post('cancel/{walletWithdrawalRequest}', [AmbassadorWalletWithdrawalRequestController::class, 'cancel']);
        });

        Route::prefix('dashboard')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('overview', [DashboardController::class, 'overview']);
        });

        Route::prefix('my-team')->group(function () {
            Route::get('/', [MyTeamController::class, 'index']);
            Route::post('/', [MyTeamController::class, 'store']);
            Route::put('/', [MyTeamController::class, 'update']);
            Route::delete('/{user}', [MyTeamController::class, 'destroy']);
        });

        Route::get('managers/autocomplete', [ManagerController::class, 'autocomplete']);
        Route::resource('managers', ManagerController::class)->only(['index', 'show'])->parameters([
            'managers' => 'user',
        ]);

        Route::prefix('accesses')->group(function () {
            Route::get('/', [AccessController::class, 'index']);
            Route::post('/', [AccessController::class, 'store']);
            Route::put('/{user}', [AccessController::class, 'update']);
            Route::delete('/{user}', [AccessController::class, 'destroy']);
        });

        Route::get('leaderboard', [LeaderboardController::class, 'index']);
        Route::get('leaderboard/project', [LeaderboardController::class, 'project']);
    });

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('registration', [AuthController::class, 'register']);
        Route::post('validate-name', [AuthController::class, 'validateName']);

        Route::get('/{provider}/redirect', [SocialAuthenticationController::class, 'redirectProvider'])
            ->where(['provider' => 'discord|twitter|telegram']);

        Route::middleware('auth')->group(function () {
            Route::get('/{provider}/callback', [SocialAuthenticationController::class, 'handleProviderCallback'])
                ->where(['provider' => 'discord|twitter|telegram']);

            Route::delete('/{provider}', [SocialAuthenticationController::class, 'destroy'])
                ->where(['provider' => 'discord|twitter|telegram']);

            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });
});
