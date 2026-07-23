<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatbotSession;
use App\Services\Chat\LiveChatService;

class LiveChatController extends Controller
{
    protected $liveChat;

    public function __construct(
        LiveChatService $liveChat
    ) {

        $this->liveChat = $liveChat;
    }

    /*
    |--------------------------------------------------------------------------
    | CHAT LIST
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        $sessions =
            ChatbotSession::latest()
            ->paginate(20);

        return view(

            'admin.live-chat.index',

            compact('sessions')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW CHAT
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {

        $session =
            ChatbotSession::findOrFail($id);

        $messages =
            \App\Models\LiveChatMessage::where(
                'chatbot_session_id',
                $session->id
            )
            ->latest()
            ->get();

        return view(

            'admin.live-chat.show',

            compact(
                'session',
                'messages'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SEND REPLY
    |--------------------------------------------------------------------------
    */

    public function reply(

        Request $request,

        $id

    ) {

        $request->validate([

            'message'
                => 'required'
        ]);

        $session =
            ChatbotSession::findOrFail($id);

        $this->liveChat
            ->reply(

                $session,

                $request->message,

                auth()->id()
            );

        return back();
    }
}