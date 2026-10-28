<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Customer;
use App\Http\Controllers\Portal;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal routes: admin area (/admin), customer area (/account), shared chat/files, Stripe webhook
|--------------------------------------------------------------------------
| Not part of the public marketing site, so no trailing-slash rule here and these URLs are
| excluded from search engines (robots.txt + noindex header in the portal layout).
*/

/* ----------------------------------------------------------------- Admin (/admin): super admin + team members */

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\Auth\LoginController::class, 'show'])->name('login');
        Route::post('login', [Admin\Auth\LoginController::class, 'login'])->name('login.submit');
        Route::get('forgot-password', [Admin\Auth\PasswordController::class, 'showForgot'])->name('password.request');
        Route::post('forgot-password', [Admin\Auth\PasswordController::class, 'sendLink'])->middleware('throttle:6,1')->name('password.email');
        Route::get('password/reset/{token}', [Admin\Auth\PasswordController::class, 'showReset'])->name('password.reset');
        Route::post('password/reset', [Admin\Auth\PasswordController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
    });

    // Everything below is for the super admin and for team members. What each team member may do is decided by their role
    // (config/permissions.php): routes are protected with can:<permission>, lists are scoped in the controllers.
    Route::middleware(['auth', 'role:team'])->group(function () {
        Route::redirect('/', '/admin/dashboard');
        Route::post('logout', [Admin\Auth\LoginController::class, 'logout'])->name('logout');
        Route::get('dashboard', Admin\DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

        /* customers */
        Route::get('customers', [Admin\CustomerController::class, 'index'])->middleware('can:customers.view')->name('customers.index');
        Route::get('customers/create', [Admin\CustomerController::class, 'create'])->middleware('can:customers.create')->name('customers.create');
        Route::post('customers', [Admin\CustomerController::class, 'store'])->middleware('can:customers.create')->name('customers.store');
        Route::get('customers/{customer}', [Admin\CustomerController::class, 'show'])->middleware('can:customers.view')->name('customers.show');
        Route::get('customers/{customer}/edit', [Admin\CustomerController::class, 'edit'])->middleware('can:customers.edit')->name('customers.edit');
        Route::put('customers/{customer}', [Admin\CustomerController::class, 'update'])->middleware('can:customers.edit')->name('customers.update');
        Route::delete('customers/{customer}', [Admin\CustomerController::class, 'destroy'])->middleware('can:customers.delete')->name('customers.destroy');

        /* orders: the list and the order page are open to every team member but scoped by OrderPolicy / Order::visibleTo */
        Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/create', [Admin\OrderController::class, 'create'])->middleware('can:orders.create')->name('orders.create');
        Route::post('orders', [Admin\OrderController::class, 'store'])->middleware('can:orders.create')->name('orders.store');
        Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/edit', [Admin\OrderController::class, 'edit'])->middleware('can:orders.edit')->name('orders.edit');
        Route::put('orders/{order}', [Admin\OrderController::class, 'update'])->middleware('can:orders.edit')->name('orders.update');
        Route::delete('orders/{order}', [Admin\OrderController::class, 'destroy'])->middleware('can:orders.delete')->name('orders.destroy');
        Route::controller(Admin\OrderActionController::class)->prefix('orders/{order}')->name('orders.')->group(function () {
            Route::post('send', 'send')->middleware('can:orders.send')->name('send');
            Route::post('deliver', 'deliver')->middleware('can:orders.deliver')->name('deliver');
            Route::post('complete', 'complete')->middleware('can:orders.complete')->name('complete');
            Route::post('cancel', 'cancel')->middleware('can:orders.cancel')->name('cancel');
        });

        /* inbox */
        Route::middleware('can:inbox.view')->group(function () {
            Route::get('inbox', [Admin\InboxController::class, 'index'])->name('inbox');
            Route::post('inbox/read-all', [Admin\InboxController::class, 'readAll'])->name('inbox.read-all');
            Route::post('inbox/messages/{message}/read', [Admin\InboxController::class, 'markRead'])->name('inbox.read');
            Route::post('inbox/messages/{message}/unread', [Admin\InboxController::class, 'markUnread'])->name('inbox.unread');
        });

        /* blog */
        Route::prefix('blog')->name('blog.')->group(function () {
            Route::get('posts', [Admin\BlogPostController::class, 'index'])->middleware('can:blog.view')->name('posts.index');
            Route::get('posts/create', [Admin\BlogPostController::class, 'create'])->middleware('can:blog.create')->name('posts.create');
            Route::post('posts', [Admin\BlogPostController::class, 'store'])->middleware('can:blog.create')->name('posts.store');
            Route::get('posts/{post}/edit', [Admin\BlogPostController::class, 'edit'])->middleware('can:blog.edit')->name('posts.edit');
            Route::put('posts/{post}', [Admin\BlogPostController::class, 'update'])->middleware('can:blog.edit')->name('posts.update');
            Route::delete('posts/{post}', [Admin\BlogPostController::class, 'destroy'])->middleware('can:blog.delete')->name('posts.destroy');
            Route::post('media', [Admin\BlogMediaController::class, 'upload'])->middleware(['can:blog.create', 'throttle:60,1'])->name('media');
            Route::middleware('can:blog.categories')->group(function () {
                Route::get('categories', [Admin\BlogCategoryController::class, 'index'])->name('categories.index');
                Route::post('categories', [Admin\BlogCategoryController::class, 'store'])->name('categories.store');
                Route::put('categories/{category}', [Admin\BlogCategoryController::class, 'update'])->name('categories.update');
                Route::delete('categories/{category}', [Admin\BlogCategoryController::class, 'destroy'])->name('categories.destroy');
            });
        });

        /* projects and tasks: open to every team member, scoped by ProjectPolicy / TaskPolicy and by role permissions */
        Route::get('projects', [Admin\ProjectController::class, 'index'])->name('projects.index');
        Route::post('projects', [Admin\ProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}', [Admin\ProjectController::class, 'show'])->name('projects.show');
        Route::get('projects/{project}/live', [Admin\ProjectController::class, 'live'])->middleware('throttle:90,1')->name('projects.live');
        Route::put('projects/{project}', [Admin\ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [Admin\ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('projects/{project}/tasks', [Admin\TaskController::class, 'store'])->name('projects.tasks.store');
        Route::get('tasks', [Admin\TaskController::class, 'mine'])->name('tasks.mine');
        Route::get('tasks/{task}', [Admin\TaskController::class, 'show'])->name('tasks.show');
        Route::get('tasks/{task}/panel', [Admin\TaskController::class, 'panel'])->name('tasks.panel');
        Route::patch('tasks/{task}', [Admin\TaskController::class, 'update'])->name('tasks.update');
        Route::post('tasks/{task}/move', [Admin\TaskController::class, 'move'])->name('tasks.move');
        Route::delete('tasks/{task}', [Admin\TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('tasks/{task}/comments', [Admin\TaskController::class, 'comment'])->middleware('throttle:60,1')->name('tasks.comment');

        /* team: members and roles */
        Route::prefix('team')->name('team.')->group(function () {
            Route::redirect('/', '/admin/team/members');
            Route::get('members', [Admin\Team\UserController::class, 'index'])->middleware('can:team.view')->name('users.index');
            Route::middleware('can:team.manage_users')->group(function () {
                Route::get('members/create', [Admin\Team\UserController::class, 'create'])->name('users.create');
                Route::post('members', [Admin\Team\UserController::class, 'store'])->name('users.store');
                Route::get('members/{member}/edit', [Admin\Team\UserController::class, 'edit'])->name('users.edit');
                Route::put('members/{member}', [Admin\Team\UserController::class, 'update'])->name('users.update');
                Route::post('members/{member}/invite', [Admin\Team\UserController::class, 'invite'])->name('users.invite');
            });
            Route::middleware('can:team.manage_roles')->group(function () {
                Route::get('roles', [Admin\Team\RoleController::class, 'index'])->name('roles.index');
                Route::get('roles/create', [Admin\Team\RoleController::class, 'create'])->name('roles.create');
                Route::post('roles', [Admin\Team\RoleController::class, 'store'])->name('roles.store');
                Route::get('roles/{role}/edit', [Admin\Team\RoleController::class, 'edit'])->name('roles.edit');
                Route::put('roles/{role}', [Admin\Team\RoleController::class, 'update'])->name('roles.update');
                Route::delete('roles/{role}', [Admin\Team\RoleController::class, 'destroy'])->name('roles.destroy');
            });
        });

        /* Stripe invoices (read-only) */
        Route::middleware('can:stripe.view')->prefix('invoices')->name('stripe.')->group(function () {
            Route::get('/', [Admin\StripeController::class, 'index'])->name('index');
            Route::get('{id}', [Admin\StripeController::class, 'show'])->where('id', 'in_[A-Za-z0-9_]+')->name('show');
        });

        /* integrations: Google Drive storage (super admin only) */
        Route::middleware('role:admin')->prefix('integrations')->name('integrations.')->group(function () {
            Route::get('/', [Admin\IntegrationsController::class, 'index'])->name('index');
            Route::get('google/connect', [Admin\IntegrationsController::class, 'connect'])->name('google.connect');
            Route::get('google/callback', [Admin\IntegrationsController::class, 'callback'])->name('google.callback');
            Route::post('drive/sync', [Admin\IntegrationsController::class, 'syncNow'])->middleware('throttle:6,1')->name('drive.sync');
            Route::post('drive/retry', [Admin\IntegrationsController::class, 'retryFailed'])->name('drive.retry');
            Route::post('drive/{connection}/activate', [Admin\IntegrationsController::class, 'activate'])->name('drive.activate');
            Route::post('drive/{connection}/move', [Admin\IntegrationsController::class, 'move'])->name('drive.move');
            Route::delete('drive/{connection}', [Admin\IntegrationsController::class, 'remove'])->name('drive.remove');
        });

        /* system: database updates + cache (super admin only) */
        Route::middleware('role:admin')->prefix('system')->name('system.')->group(function () {
            Route::get('/', [Admin\SystemController::class, 'show'])->name('index');
            Route::post('migrate', [Admin\SystemController::class, 'migrate'])->middleware('throttle:6,1')->name('migrate');
            Route::post('cache', [Admin\SystemController::class, 'clearCache'])->middleware('throttle:12,1')->name('cache');
            Route::post('mcp-keys', [Admin\SystemController::class, 'createKey'])->middleware('throttle:10,1')->name('mcp.create');
            Route::delete('mcp-keys/{token}', [Admin\SystemController::class, 'revokeKey'])->name('mcp.revoke');
        });

        /* own profile: every team member */
        Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings');
        Route::put('settings/profile', [Admin\SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::put('settings/password', [Admin\SettingsController::class, 'updatePassword'])->name('settings.password');
    });
});

/* ----------------------------------------------------------------- Customer (/account) */

Route::prefix('account')->name('customer.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Customer\Auth\LoginController::class, 'show'])->name('login');
        Route::post('login', [Customer\Auth\LoginController::class, 'sendCode'])->name('login.send');
        Route::get('login/verify', [Customer\Auth\LoginController::class, 'showVerify'])->name('login.verify');
        Route::post('login/verify', [Customer\Auth\LoginController::class, 'verify'])->name('login.verify.submit');
    });

    Route::middleware(['auth', 'role:customer', 'profile.complete'])->group(function () {
        Route::post('logout', [Customer\Auth\LoginController::class, 'logout'])->name('logout');
        Route::get('welcome', [Customer\WelcomeController::class, 'show'])->name('welcome');
        Route::post('welcome', [Customer\WelcomeController::class, 'save'])->name('welcome.save');
        Route::get('requests/new', [Customer\RequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [Customer\RequestController::class, 'store'])->middleware('throttle:10,1')->name('requests.store');
        Route::get('/', Customer\DashboardController::class)->name('dashboard');

        Route::get('invoices', [Customer\InvoiceController::class, 'index'])->name('invoices');
        Route::get('orders/{order}', [Customer\OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/accept', [Customer\OrderController::class, 'accept'])->name('orders.accept');
        Route::post('orders/{order}/revision', [Customer\OrderController::class, 'revision'])->name('orders.revision');
        Route::post('orders/{order}/pay', [Customer\CheckoutController::class, 'start'])->name('orders.pay');
        Route::get('orders/{order}/paid', [Customer\CheckoutController::class, 'returned'])->name('orders.paid');

        Route::get('profile', [Customer\ProfileController::class, 'edit'])->name('profile');
        Route::put('profile', [Customer\ProfileController::class, 'update'])->name('profile.update');
    });
});

/* ----------------------------------------------------------------- Shared (admin + customer, authorised by policy) */

Route::middleware('auth')->prefix('portal')->name('portal.')->group(function () {
    Route::get('orders/{order}/messages', [Portal\ChatController::class, 'index'])->name('chat.index');
    Route::post('orders/{order}/messages', [Portal\ChatController::class, 'store'])->middleware('throttle:60,1')->name('chat.store');
    Route::post('orders/{order}/chunks', [Portal\ChatController::class, 'chunk'])->middleware('throttle:1200,1')->name('chat.chunk');
    Route::get('orders/{order}/messages/{message}/thread', [Portal\ChatController::class, 'thread'])->name('chat.thread');
    Route::post('orders/{order}/messages/{message}/react', [Portal\ChatController::class, 'react'])->middleware('throttle:120,1')->name('chat.react');
    Route::get('orders/{order}/members', [Portal\ChatMemberController::class, 'index'])->name('chat.members');
    Route::post('orders/{order}/members', [Portal\ChatMemberController::class, 'store'])->name('chat.members.store');
    Route::delete('orders/{order}/members/{user}', [Portal\ChatMemberController::class, 'destroy'])->name('chat.members.destroy');
    Route::get('orders/{order}/tags', [Portal\ChatTagController::class, 'search'])->middleware('throttle:240,1')->name('chat.tags');
    Route::get('cards/tasks/{task}', [Portal\CardController::class, 'task'])->name('cards.task');
    Route::get('cards/projects/{project}', [Portal\CardController::class, 'project'])->name('cards.project');
    Route::get('files/{file}', [Portal\FileController::class, 'show'])->name('files.show');
    Route::get('orders/{order}/invoice', [Portal\InvoiceController::class, 'show'])->name('orders.invoice');
});

/* ----------------------------------------------------------------- Stripe webhook (signature-verified, CSRF-exempt) */

/* MCP: lets an admin's AI assistant (Claude) work in the portal. Authenticated by an access key, not by a session. */
Route::post('mcp', [App\Http\Controllers\McpController::class, 'handle'])->middleware('throttle:600,1')->name('mcp');
Route::post('mcp/{key}', [App\Http\Controllers\McpController::class, 'handle'])->middleware('throttle:600,1')->where('key', 'arc_[A-Za-z0-9]{48}')->name('mcp.key');
Route::match(['get', 'delete', 'put', 'patch'], 'mcp/{key?}', [App\Http\Controllers\McpController::class, 'notAllowed'])->name('mcp.nope');

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');
