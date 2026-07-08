<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RAG App') — Laravel RAG</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
        }

        nav {
            background: #1e293b;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            height: 56px;
            gap: 1.5rem;
        }

        nav .brand {
            color: #f8fafc;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            margin-right: auto;
        }

        nav a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            transition: background 0.15s, color 0.15s;
        }

        nav a:hover, nav a.active {
            background: #334155;
            color: #f8fafc;
        }

        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
            padding: 1.75rem;
            margin-bottom: 1.25rem;
        }

        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.25rem; }
        h2 { font-size: 1.15rem; font-weight: 600; margin-bottom: 0.75rem; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1.1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.88; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-danger  { background: #dc2626; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #1e293b; }

        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 0.35rem; color: #475569; }

        input[type=text], input[type=password], input[type=file], textarea, select {
            width: 100%;
            padding: 0.55rem 0.85rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
            background: #f8fafc;
            transition: border-color 0.15s;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #2563eb;
            background: #fff;
        }
        textarea { resize: vertical; min-height: 120px; }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { padding: 0.65rem 0.85rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        .source-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.85rem;
            margin-bottom: 0.65rem;
            font-size: 0.85rem;
        }
        .source-card .source-title { font-weight: 600; color: #1d4ed8; margin-bottom: 0.35rem; }
        .source-card .excerpt { color: #64748b; line-height: 1.5; }
    </style>
    @stack('head')
</head>
<body>

<nav>
    <a href="{{ route('chat.index') }}" class="brand">&#129302; RAG Laravel</a>
    <a href="{{ route('chat.index') }}" class="{{ request()->routeIs('chat.*') ? 'active' : '' }}">Doc Chat</a>
    <a href="{{ route('db-chat.index') }}" class="{{ request()->routeIs('db-chat.*') ? 'active' : '' }}">DB Chat</a>
    <a href="{{ route('documents.index') }}" class="{{ request()->routeIs('documents.*') ? 'active' : '' }}">Documents</a>
</nav>

<div class="container">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @yield('content')

</div>

@stack('scripts')
</body>
</html>
