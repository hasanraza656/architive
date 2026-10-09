<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Every portal e-mail goes through here so a mail-server problem never breaks a page:
 * failures are logged and reported back as `false` (callers decide whether to warn the user).
 */
class PortalMailer
{
    public function send(string|array $to, Mailable $mailable): bool
    {
        try {
            Mail::to($to)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Portal e-mail failed: ' . $e->getMessage(), ['mailable' => get_class($mailable)]);

            return false;
        }
    }

    /** The inbox that receives admin notifications (ADMIN_EMAIL, falling back to the first admin account). */
    public function adminAddress(): ?string
    {
        return config('site.admin_email') ?: User::where('role', User::ROLE_ADMIN)->orderBy('id')->value('email');
    }

    public function toAdmin(Mailable $mailable): bool
    {
        $to = $this->adminAddress();

        return $to ? $this->send($to, $mailable) : false;
    }
}
