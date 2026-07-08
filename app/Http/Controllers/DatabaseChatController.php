<?php

namespace App\Http\Controllers;

use App\Services\DatabaseQueryService;
use Illuminate\Http\Request;

class DatabaseChatController extends Controller
{
    public function __construct(private DatabaseQueryService $service) {}

    public function index()
    {
        $conn = $this->defaultConnection();
        return view('db-chat.index', compact('conn'));
    }

    public function ask(Request $request)
    {
        $request->validate([
            'question'    => 'required|string|min:3|max:1000',
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $conn = [
            'host'     => $request->input('db_host'),
            'port'     => $request->input('db_port'),
            'database' => $request->input('db_database'),
            'username' => $request->input('db_username'),
            'password' => $request->input('db_password', ''),
        ];

        try {
            $result = $this->service->ask($request->input('question'), $conn);
        } catch (\Throwable $e) {
            $result = [
                'answer'  => null,
                'sql'     => null,
                'columns' => [],
                'rows'    => [],
                'total'   => 0,
                'error'   => $e->getMessage(),
            ];
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return view('db-chat.index', [
            'conn'     => $conn,
            'question' => $request->input('question'),
            'answer'   => $result['answer'],
            'sql'      => $result['sql'],
            'columns'  => $result['columns'],
            'rows'     => $result['rows'],
            'total'    => $result['total'],
            'error'    => $result['error'],
        ]);
    }

    public function testConnection(Request $request)
    {
        $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        try {
            $pdo    = $this->service->connect([
                'host'     => $request->input('db_host'),
                'port'     => $request->input('db_port'),
                'database' => $request->input('db_database'),
                'username' => $request->input('db_username'),
                'password' => $request->input('db_password', ''),
            ]);
            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            return response()->json(['success' => true, 'tables' => $tables]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    private function defaultConnection(): array
    {
        return [
            'host'     => config('database.connections.mysql.host', '127.0.0.1'),
            'port'     => config('database.connections.mysql.port', 3306),
            'database' => config('database.connections.mysql.database', ''),
            'username' => config('database.connections.mysql.username', 'root'),
            'password' => '',
        ];
    }
}
