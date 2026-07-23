<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\AIChatMessage;

class AIChatController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CHAT LIST
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ) {

        $query =

            AIChatMessage::query();

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if (
            $request->search
        ) {

            $search =
                $request->search;

            $query->where(function($q) use ($search) {

                $q->where(

                    'user_message',

                    'LIKE',

                    '%' . $search . '%'

                )

                ->orWhere(

                    'ai_reply',

                    'LIKE',

                    '%' . $search . '%'

                )

                ->orWhere(

                    'session_id',

                    'LIKE',

                    '%' . $search . '%'

                )

                ->orWhere(

                    'ip_address',

                    'LIKE',

                    '%' . $search . '%'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | DATE FILTER
        |--------------------------------------------------------------------------
        */

        if (
            $request->from_date
        ) {

            $query->whereDate(

                'created_at',

                '>=',

                $request->from_date
            );
        }

        if (
            $request->to_date
        ) {

            $query->whereDate(

                'created_at',

                '<=',

                $request->to_date
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GET DATA
        |--------------------------------------------------------------------------
        */

        $messages =

            $query

            ->latest()

            ->paginate(50)

            ->appends(
                request()->query()
            );

        return view(

            'backend.ai.chat.index',

            compact(
                'messages'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW CHAT
    |--------------------------------------------------------------------------
    */

    public function show(
        $session
    ) {

        $messages =

            AIChatMessage::query()

            ->where(
                'session_id',
                $session
            )

            ->latest()

            ->get();

        return view(

            'backend.ai.chat.show',

            compact(
                'messages',
                'session'
            )
        );
    }
    /*
|--------------------------------------------------------------------------
| ADMIN REPLY
|--------------------------------------------------------------------------
*/

public function reply(
    Request $request,
    $session
) {

    $request->validate([

        'reply'
            => 'required'
    ]);

    /*
    |--------------------------------------------------------------------------
    | SAVE ADMIN MESSAGE
    |--------------------------------------------------------------------------
    */

    AIChatMessage::create([

    'session_id'
        => $session,

    'user_message'
        => null,

    'ai_reply'
        => $request->reply,

    'ip_address'
        => request()->ip(),

    'source'
        => 'admin',

    'sender'
        => 'admin',

    'is_admin'
        => 1,

    'human_mode'
        => 1
]);

    return back()->with(

        'success',

        'Reply sent successfully.'
    );
}
}