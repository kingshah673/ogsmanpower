<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class CloudMessageController extends Controller
{
    public $messaging;

    public $factory;

    public function __construct()
    {
        try {
            $this->factory = (new Factory)->withServiceAccount(storage_path('firebase_credentials.json'));
            $this->messaging = $this->factory->createMessaging();
        } catch (\Throwable $e) {
            \Log::warning('Firebase credentials not available: ' . $e->getMessage());
        }
    }

    public function storeTokenAnonymous(Request $request)
    {

        $request->validate([
            'token' => 'required',
        ]);

        try {
            $deviceToken = DeviceToken::firstOrCreate([
                'device_token' => $request->token,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => [
                    'message' => $e->getMessage(),
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'message' => 'Token successfully stored!',
                'device_token' => $deviceToken,
            ],
        ]);

    }

    public function storeToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        try {
            $deviceToken = DeviceToken::firstOrCreate([
                'user_id' => auth()->user()->id,
                'device_token' => $request->token,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => [
                    'message' => $e->getMessage(),
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'message' => 'Token successfully stored!',
                'device_token' => $deviceToken,
            ],
        ]);
    }

    // public function
    public function sendNotification(Request $request)
    {

        $request->validate([
            'title' => 'required',
            'body' => 'required',
        ]);

        $user = auth()->user();
        $deviceTokens = DeviceToken::where('user_id', $user->id)->pluck('device_token')->all();

        if (empty($deviceTokens)) {
            return response()->json([
                'data' => [
                    'message' => 'Not device token found',
                ],
            ]);
        }

        $notification = Notification::fromArray([
            'title' => $request->title,
            'body' => $request->body,
        ]);

        $topic = 'topic-A';
        $message = CloudMessage::withTarget('topic', $topic)->withNotification($notification);

        try {
            $response = $this->messaging->sendMulticast($message, $deviceTokens);

            return response()->json([
                'data' => [
                    'response' => $response,
                ],
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'data' => [
                    'message' => $e->getMessage(),
                ],
            ], 500);

        }

    }

    public function sendNotificationAllUsers(Request $request)
    {

        $request->validate([
            'title' => 'required',
            'body' => 'required',
        ]);

        $deviceTokens = DeviceToken::pluck('device_token')->all();

        if (empty($deviceTokens)) {
            return response()->json([
                'data' => [
                    'message' => 'Not device token found',
                ],
            ]);
        }

        $notification = Notification::fromArray([
            'title' => $request->title,
            'body' => $request->body,
        ]);

        $topic = 'topic-A';
        $message = CloudMessage::withTarget('topic', $topic)->withNotification($notification);

        try {
            $response = $this->messaging->sendMulticast($message, $deviceTokens);

            return response()->json([
                'data' => [
                    'response' => $response,
                ],
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'data' => [
                    'message' => $e->getMessage(),
                ],
            ], 500);

        }
    }
}
