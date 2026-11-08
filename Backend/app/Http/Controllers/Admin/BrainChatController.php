<?php

namespace App\Http\Controllers\Admin;

use App\Brain\CommandRouter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BrainChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** POST /admin/brain/chat — natural language command */
    public function ask(Request $request, CommandRouter $router)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $out = $router->handle($request->input('message'), (int) $user->id);

        return response()->json($out);
    }

    /** GET /admin/brain/history */
    public function history()
    {
        $rows = DB::table('brain_chats')
            ->where('user_id', Auth::id())
            ->orderByDesc('id')->limit(30)->get()
            ->reverse()->values();

        $messages = collect([
            [
                'role' => 'system_note',
                'text' => 'Master Brain online. Try: "plan cluster for online puja in india" · "schedule upcoming hindu festival wishes this month" · "status report" · "run seo audit"',
            ]
        ]);

        foreach ($rows as $c) {
            if (!empty($c->message)) {
                $messages->push([
                    'role' => 'user',
                    'text' => $c->message,
                    'at'   => $c->created_at,
                ]);
            }
            $messages->push([
                'role'    => 'brain',
                'text'    => $c->response,
                'actions' => json_decode((string) $c->actions, true),
                'at'      => $c->created_at,
            ]);
        }

        return response()->json([
            'messages' => $messages,
        ]);
    }
}
