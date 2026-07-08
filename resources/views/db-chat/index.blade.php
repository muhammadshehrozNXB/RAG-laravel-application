@extends('layouts.app')
@section('title', 'Database Chat')

@section('content')
<h1>Database Chat</h1>

{{-- Connection card --}}
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem;">
        <h2 style="margin:0;">Database Connection</h2>
        <div id="conn-status" style="font-size:.8rem; color:#64748b;"></div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:.65rem;" id="conn-fields">
        <div class="form-group" style="margin:0;">
            <label>Host</label>
            <input type="text" id="db_host" value="{{ $conn['host'] }}" placeholder="127.0.0.1">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Port</label>
            <input type="text" id="db_port" value="{{ $conn['port'] }}" placeholder="3306">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Database</label>
            <input type="text" id="db_database" value="{{ $conn['database'] }}" placeholder="my_database">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Username</label>
            <input type="text" id="db_username" value="{{ $conn['username'] }}" placeholder="root">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Password</label>
            <input type="password" id="db_password" value="password12345" placeholder="(leave blank if none)" autocomplete="new-password">
        </div>
    </div>

    <div style="margin-top:.75rem; display:flex; align-items:center; gap:.75rem;">
        <button class="btn btn-secondary" id="test-btn" type="button">Test Connection</button>
        <span id="tables-badge" style="display:none; font-size:.8rem; color:#166534; padding:.2rem .65rem; border-radius:999px;"></span>
    </div>
</div>

{{-- Ask card --}}
<div class="card">
    <form method="POST" action="{{ route('db-chat.ask') }}" id="ask-form">
        @csrf
        <input type="hidden" name="db_host"     id="f_host">
        <input type="hidden" name="db_port"     id="f_port">
        <input type="hidden" name="db_database" id="f_database">
        <input type="hidden" name="db_username" id="f_username">
        <input type="hidden" name="db_password" id="f_password">

        <div class="form-group">
            <label for="question">Ask anything about your data</label>
            <textarea name="question" id="question" rows="3"
                placeholder="e.g. Show employees older than 20 | How many orders were placed last month? | List top 5 customers by revenue">{{ old('question', $question ?? '') }}</textarea>
        </div>
        <div style="display:flex; gap:.75rem; align-items:center;">
            <button type="submit" class="btn btn-primary" id="submit-btn">Ask</button>
            <span id="loading" style="display:none; color:#64748b; font-size:.875rem;">Generating SQL &amp; fetching results…</span>
        </div>
    </form>
</div>

@if(!empty($error))
<div class="alert alert-error">{{ $error }}</div>
@endif

@isset($answer)

{{-- Answer --}}
<div class="card">
    <h2>Answer</h2>
    <p style="line-height:1.75; white-space:pre-wrap;">{{ $answer }}</p>
</div>

{{-- Generated SQL --}}
@if($sql)
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.5rem;">
        <h2 style="margin:0;">Generated SQL</h2>
        <span class="badge badge-gray">{{ $total }} row(s) total</span>
    </div>
    <pre style="background:#f1f5f9; border-radius:8px; padding:1rem; font-size:.825rem; overflow-x:auto; white-space:pre-wrap; margin:0;">{{ $sql }}</pre>
</div>
@endif

{{-- Results table --}}
@if(!empty($rows))
<div class="card">
    <h2>Results <span style="font-weight:400; color:#64748b; font-size:.9rem;">(showing {{ count($rows) }} of {{ $total }})</span></h2>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    @foreach($columns as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($columns as $col)
                            <td>{{ $row[$col] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endisset

@if(!isset($answer) && empty($error))
<div class="card" style="color:#64748b; font-size:.9rem; text-align:center; padding:2rem;">
    Connect to a database above, then type any question in plain English — the system will generate and run the SQL automatically.
</div>
@endif

@endsection

@push('scripts')
<script>
function connValues() {
    return {
        host:     document.getElementById('db_host').value.trim(),
        port:     document.getElementById('db_port').value.trim(),
        database: document.getElementById('db_database').value.trim(),
        username: document.getElementById('db_username').value.trim(),
        password: document.getElementById('db_password').value,
    };
}

// Test connection
document.getElementById('test-btn').addEventListener('click', async function () {
    const v = connValues();
    const status = document.getElementById('conn-status');
    const badge  = document.getElementById('tables-badge');
    status.textContent = 'Testing…';
    badge.style.display = 'none';
    this.disabled = true;

    const fd = new FormData();
    fd.append('_token',      document.querySelector('meta[name="csrf-token"]').content);
    fd.append('db_host',     v.host);
    fd.append('db_port',     v.port);
    fd.append('db_database', v.database);
    fd.append('db_username', v.username);
    fd.append('db_password', v.password);

    try {
        const res  = await fetch('{{ route('db-chat.test') }}', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            status.textContent = 'Connected';
            status.style.color = '#166534';
            badge.textContent  = json.tables.length + ' tables: ' + json.tables.join(', ');
            badge.style.display = '';
        } else {
            status.textContent = 'Failed: ' + json.error;
            status.style.color = '#991b1b';
        }
    } catch {
        status.textContent = 'Request error';
        status.style.color = '#991b1b';
    } finally {
        this.disabled = false;
    }
});

// Sync hidden fields before submit
document.getElementById('ask-form').addEventListener('submit', function (e) {
    const v = connValues();
    document.getElementById('f_host').value     = v.host;
    document.getElementById('f_port').value     = v.port;
    document.getElementById('f_database').value = v.database;
    document.getElementById('f_username').value = v.username;
    document.getElementById('f_password').value = v.password;

    document.getElementById('submit-btn').disabled = true;
    document.getElementById('loading').style.display = 'inline';
});
</script>
@endpush
