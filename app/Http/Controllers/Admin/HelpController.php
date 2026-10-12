<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $systemCheck = [
            'company_name' => Setting::get('company_name'),
            'company_email' => Setting::get('company_email'),
            'company_phone' => Setting::get('company_phone'),
            'shop_enabled' => Setting::get('shop_enabled') === '1',
            'has_google_analytics' => ! empty(Setting::get('google_analytics_id')),
            'has_google_tag_manager' => ! empty(Setting::get('google_tag_manager_id')),
            'has_meta_pixel' => ! empty(Setting::get('meta_pixel_id')),
            'notifications_mail_enabled' => Setting::get('operational_reminders_email_enabled') === '1',
            'users_count' => User::count(),
        ];

        return Inertia::render('Admin/Help/Index', [
            'systemCheck' => $systemCheck,
        ]);
    }
}
