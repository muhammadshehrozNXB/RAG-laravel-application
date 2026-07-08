<?php

namespace App\Http\Controllers;

use App\Services\RagService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private RagService $ragService)
    {
    }

    public function index()
    {
        return view('chat.index');
    }

    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|min:3|max:1000',
        ]);

        try {
            $result = $this->ragService->answer($request->input('question'));

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return view('chat.index', [
                'question' => $request->input('question'),
                'answer'   => $result['answer'],
                'sources'  => $result['sources'],
            ]);
        } catch (\Exception $e) {
            $error = $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['error' => $error], 500);
            }

            return back()->withErrors(['error' => 'Failed to get answer: ' . $error]);
        }
    }
}
