<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin;
use App\Mail\SendOTP;
use Twilio\Rest\Client as TwilioClient;

class OTPController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Show OTP Page
    |--------------------------------------------------------------------------
    */
    public function showVerifyForm()
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            if ($admin && ($admin->is_otp_verified ?? true)) {
                return redirect()->intended(route('admin.dashboard'));
            }
        }

        if (Auth::guard('user')->check()) {
            $user = Auth::guard('user')->user();
            if ($user && ($user->is_otp_verified ?? false)) {
                return redirect()->intended(user_home_route($user));
            }

            return view('frontend.auth.otp');
        }

        // Guests cannot verify OTP — send them to login
        return redirect()->route('login');
    }

    /*
    |--------------------------------------------------------------------------
    | Send OTP
    |--------------------------------------------------------------------------
    */
    public function sendOTP(Request $request)
    {
        $authUser = Auth::user() ?: Auth::guard('admin')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $model = $authUser instanceof Admin ? Admin::class : User::class;
        // Use the authenticated user's own ID — never trust the client-supplied user_id
        $user  = $model::findOrFail($authUser->id);

        // 🔐 Secure OTP
        $otp = random_int(100000, 999999);

        $user->otp_code   = $otp;
        $user->otp_expiry = Carbon::now()->addMinutes(10);
        $user->save();

        try {

            if ($request->via === 'email') {

                if (empty($user->email)) {
                    return response()->json(['success' => false, 'message' => 'No email address on your account.']);
                }

                Mail::to($user->email)->send(new SendOTP($otp, $user->name));

            } elseif ($request->via === 'whatsapp') {

                $whatsapp = trim($user->whatsapp ?? '');
                if (empty($whatsapp)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No WhatsApp number saved on your account. Please update your profile first.'
                    ]);
                }

                $this->sendWhatsAppOTP($user, $otp);

            } elseif ($request->via === 'sms') {

                $phone = trim($user->phone ?? $user->whatsapp ?? '');
                if (empty($phone)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No phone number saved on your account. Please update your profile first.'
                    ]);
                }

                $this->sendSmsOTP($phone, $otp);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid method'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully'
            ]);

        } catch (\Exception $e) {

            Log::error('OTP Send Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | WhatsApp OTP (AUTH TEMPLATE - FIXED)
    |--------------------------------------------------------------------------
    */
    private function sendWhatsAppOTP($user, $otp)
    {
        try {

            $phone = $this->formatPhoneGlobal(
                $user->whatsapp,
                $user->country_code ?? null
            );

            $url = "https://graph.facebook.com/" .
                env('WHATSAPP_VERSION') . "/" .
                env('WHATSAPP_PHONE_ID') . "/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => 'otp_code',
                    'language' => [
                        'code' => 'en'
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => (string)$otp
                                ]
                            ]
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'copy_code',
                            'index' => '0',
                            'parameters' => [
                                [
                                    'type' => 'coupon_code',
                                    'coupon_code' => (string)$otp
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $response = Http::withToken(env('WHATSAPP_TOKEN'))
                ->post($url, $payload);

            Log::info('WhatsApp OTP SENT', [
                'phone' => substr($phone, 0, -4) . '****',
                'response_status' => $response->status()
            ]);

            return $response->json();

        } catch (\Exception $e) {

            Log::error('WhatsApp OTP Error', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SMS OTP via Twilio
    |--------------------------------------------------------------------------
    */
    private function sendSmsOTP($phone, $otp)
    {
        $phone = $this->formatPhoneGlobal($phone);

        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        $twilio = new TwilioClient(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        );

        $from = env('TWILIO_SMS_NUMBER', env('TWILIO_WHATSAPP_NUMBER'));

        $twilio->messages->create($phone, [
            'from' => $from,
            'body' => "Your Career WorkForce OTP is: {$otp}. Valid for 10 minutes.",
        ]);

        Log::info('SMS OTP sent', ['phone' => $phone]);
    }

    /*
    |--------------------------------------------------------------------------
    | Global Phone Formatter
    |--------------------------------------------------------------------------
    */
    private function formatPhoneGlobal($phone, $countryCode = null)
    {
        // Strip everything except digits
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Strip leading 00 (international dialing prefix) or single 0
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Prepend country code if not already present
        if ($countryCode) {
            $countryCode = preg_replace('/[^0-9]/', '', $countryCode);
            if (!str_starts_with($phone, $countryCode)) {
                $phone = $countryCode . $phone;
            }
        }

        return $phone;
    }

    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    */
    public function verifyOTP(Request $request)
    {
        $authUser = Auth::user() ?: Auth::guard('admin')->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $model = $authUser instanceof Admin ? Admin::class : User::class;
        // Use the authenticated user's own ID — never trust the client-supplied user_id
        $user  = $model::findOrFail($authUser->id);

        $enteredOtp = trim((string)$request->otp);
        $savedOtp   = trim((string)$user->otp_code);

        if (
            $enteredOtp === $savedOtp &&
            $user->otp_expiry &&
            Carbon::now()->lte(Carbon::parse($user->otp_expiry))
        ) {

            $user->otp_code = null;
            $user->otp_expiry = null;
            $user->is_otp_verified = true;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired OTP'
        ]);
    }
}