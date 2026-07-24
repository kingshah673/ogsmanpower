<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\ChatLead;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeadFloatController extends Controller
{
    public function track(Request $request)
    {
        $data = $request->validate([
            'source' => ['required', 'in:floating_whatsapp,floating_email,floating_register'],
        ]);

        $config = $this->leadConfig();
        $destination = match ($data['source']) {
            'floating_whatsapp' => 'https://wa.me/' . $config['whatsapp'],
            'floating_email' => 'mailto:' . $config['email'],
            'floating_register' => $config['register'],
            default => url('/'),
        };

        ChatLead::create([
            'full_name' => 'Floating CTA Visitor',
            'phone' => $data['source'] === 'floating_whatsapp' ? $config['whatsapp'] : null,
            'email' => $data['source'] === 'floating_email' ? $config['email'] : null,
            'session_id' => $request->session()->getId(),
            'source' => $data['source'],
            'category' => 'lead_generation',
            'status' => 'new',
            'priority' => 'medium',
            'message' => 'Clicked site-wide floating CTA: ' . $data['source'],
        ]);

        return response()->json([
            'ok' => true,
            'redirect' => $destination,
        ]);
    }

    private function leadConfig(): array
    {
        $about = [];
        if (Schema::hasTable('about_config')) {
            $about = DB::table('about_config')->pluck('cfg_value', 'cfg_key')->toArray();
        }

        $siteEmail = optional(Setting::first())->email;

        $email = $about['email_address'] ?? $siteEmail ?? 'info@ogsmanpower.com';
        $whatsapp = preg_replace('/[^0-9]/', '', (string) ($about['whatsapp_number'] ?? '923005352636')) ?: '923005352636';
        $register = $about['register_url'] ?? route('register');

        if ($register && ! str_starts_with($register, 'http')) {
            $register = url('/' . ltrim($register, '/'));
        }

        return [
            'whatsapp' => $whatsapp,
            'email' => $email,
            'register' => $register ?: route('register'),
        ];
    }
}
