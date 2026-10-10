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

/* ----------------------------------------------------------------- Admin (/admin) */

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\Auth\LoginController::class, 'show'])->name('login');
        Route::post('login', [Admin\Auth\LoginController::class, 'login'])->name('login.submit');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::redirect('/', '/admin/dashboard');
        Route::post('logout', [Admin\Auth\LoginController::class, 'logout'])->name('logout');
        Route::get('dashboard', Admin\DashboardController::class)->name('dashboard');

        Route::resource('customers', Admin\CustomerController::class);

        Route::prefix('blog')->name('blog.')->group(function () {
            Route::resource('posts', Admin\BlogPostController::class)->except('show');
            Route::post('media', [Admin\BlogMediaController::class, 'upload'])->middleware('throttle:60,1')->name('media');
            Route::get('categories', [Admin\BlogCategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [Admin\BlogCategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [Admin\BlogCategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [Admin\BlogCategoryController::class, 'destroy'])->name('categories.destroy');
        });

        Route::get('inbox', [Admin\InboxController::class, 'index'])->name('inbox');
        Route::post('inbox/read-all', [Admin\InboxController::class, 'readAll'])->name('inbox.read-all');
        Route::post('inbox/messages/{message}/read', [Admin\InboxController::class, 'markRead'])->name('inbox.read');
        Route::post('inbox/messages/{message}/unread', [Admin\InboxController::class, 'markUnread'])->name('inbox.unread');

        Route::resource('orders', Admin\OrderController::class);
        Route::controller(Admin\OrderActionController::class)->prefix('orders/{order}')->name('orders.')->group(function () {
            Route::post('send', 'send')->name('send');
            Route::post('deliver', 'deliver')->name('deliver');
            Route::post('complete', 'complete')->name('complete');
            Route::post('cancel', 'cancel')->name('cancel');
        });

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
    Route::post('orders/{order}/messages', [Portal\ChatController::class, 'store'])->middleware('throttle:40,1')->name('chat.store');
    Route::get('files/{file}', [Portal\FileController::class, 'show'])->name('files.show');
    Route::get('orders/{order}/invoice', [Portal\InvoiceController::class, 'show'])->name('orders.invoice');
});

/* ----------------------------------------------------------------- Stripe webhook (signature-verified, CSRF-exempt) */

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');
