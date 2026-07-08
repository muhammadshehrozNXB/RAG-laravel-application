@extends('layouts.app')
@section('title', 'Upload Document')

@section('content')
<h1>Upload Document</h1>

<div class="card">
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" id="upload-form">
        @csrf

        <div class="form-group">
            <label>Source Type</label>
            <div style="display:flex; gap:1.25rem; margin-top:.25rem; flex-wrap:wrap;">
                <label style="display:flex; align-items:center; gap:.4rem; font-weight:400; cursor:pointer;">
                    <input type="radio" name="source" value="file" id="src-file" {{ old('source','file')==='file'?'checked':'' }}>
                    Upload a file (.txt / .md / .pdf)
                </label>
                <label style="display:flex; align-items:center; gap:.4rem; font-weight:400; cursor:pointer;">
                    <input type="radio" name="source" value="text" id="src-text" {{ old('source')==='text'?'checked':'' }}>
                    Paste text
                </label>
                <label style="display:flex; align-items:center; gap:.4rem; font-weight:400; cursor:pointer;">
                    <input type="radio" name="source" value="database" id="src-db" {{ old('source')==='database'?'checked':'' }}>
                    Query database
                </label>
            </div>
        </div>

        {{-- File upload --}}
        <div id="file-section">
            <div class="form-group">
                <label for="file">File</label>
                <input type="file" name="file" id="file" accept=".txt,.md,.pdf">
                <div style="margin-top:.5rem; font-size:.8rem; color:#64748b;">
                    Need a test file?
                    <a href="{{ route('documents.sample') }}" style="color:#3b82f6; text-decoration:underline;">
                        Download sample PDF
                    </a>
                    &mdash; an AI overview document ready to ingest.
                </div>
            </div>
        </div>

        {{-- Paste text --}}
        <div id="text-section" style="display:none;">
            <div class="form-group">
                <label for="title">Document Title</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" placeholder="My Knowledge Base">
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea name="content" id="content" rows="10" placeholder="Paste your document text here…">{{ old('content') }}</textarea>
            </div>
        </div>

        {{-- Database query --}}
        <div id="db-section" style="display:none;">

            {{-- Connection toggle --}}
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; cursor:pointer;">
                    <input type="checkbox" id="use-app-db" checked style="width:auto;">
                    Use this application's database
                    <span style="font-size:.78rem; color:#94a3b8;">({{ config('database.connections.mysql.database') }}@{{ config('database.connections.mysql.host') }})</span>
                </label>
            </div>

            <div id="custom-conn" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:1rem; margin-bottom:1rem;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem;">
                    <div class="form-group" style="margin:0;">
                        <label>Host</label>
                        <input type="text" name="db_host" value="{{ old('db_host','127.0.0.1') }}" placeholder="127.0.0.1">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Port</label>
                        <input type="text" name="db_port" value="{{ old('db_port','3306') }}" placeholder="3306">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Database</label>
                        <input type="text" name="db_database" value="{{ old('db_database') }}" placeholder="my_database">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Username</label>
                        <input type="text" name="db_username" value="{{ old('db_username','root') }}" placeholder="root">
                    </div>
                    <div class="form-group" style="margin:0; grid-column:1/-1;">
                        <label>Password</label>
                        <input type="password" name="db_password" value="" placeholder="(leave blank if none)" autocomplete="new-password">
                    </div>
                </div>
            </div>

            {{-- SQL query --}}
            <div class="form-group">
                <label for="db_query">SQL Query <span style="font-weight:400; color:#94a3b8;">(SELECT only)</span></label>
                <textarea name="db_query" id="db_query" rows="5"
                    style="font-family:monospace; font-size:.875rem;"
                    placeholder="SELECT id, name, email FROM users LIMIT 100">{{ old('db_query') }}</textarea>
            </div>

            <div style="margin-bottom:1rem;">
                <button type="button" class="btn btn-secondary" id="preview-btn">Preview Results</button>
                <span id="preview-status" style="font-size:.8rem; color:#64748b; margin-left:.75rem;"></span>
            </div>

            {{-- Preview table --}}
            <div id="preview-box" style="display:none; margin-bottom:1.25rem; overflow-x:auto; border:1px solid #e2e8f0; border-radius:8px;">
                <div id="preview-meta" style="padding:.6rem 1rem; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:.8rem; color:#475569;"></div>
                <div style="overflow-x:auto;">
                    <table id="preview-table" style="font-size:.8rem; min-width:100%;"></table>
                </div>
            </div>

            {{-- Document title --}}
            <div class="form-group">
                <label for="db_title">Document Title</label>
                <input type="text" name="db_title" id="db_title" value="{{ old('db_title') }}" placeholder="e.g. Users export May 2026">
            </div>
        </div>

        <div style="display:flex; gap:.75rem; align-items:center; margin-top:1.25rem;">
            <button type="submit" class="btn btn-primary" id="submit-btn">Ingest Document</button>
            <a href="{{ route('documents.index') }}" class="btn btn-secondary">Cancel</a>
            <span id="loading" style="display:none; color:#64748b; font-size:.875rem;">Processing…</span>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const radioFile = document.getElementById('src-file');
const radioText = document.getElementById('src-text');
const radioDb   = document.getElementById('src-db');
const fileSection = document.getElementById('file-section');
const textSection = document.getElementById('text-section');
const dbSection   = document.getElementById('db-section');

function toggleSections() {
    fileSection.style.display = radioFile.checked ? '' : 'none';
    textSection.style.display = radioText.checked ? '' : 'none';
    dbSection.style.display   = radioDb.checked   ? '' : 'none';
}

[radioFile, radioText, radioDb].forEach(r => r.addEventListener('change', toggleSections));
toggleSections();

// Custom connection toggle
const useAppDb   = document.getElementById('use-app-db');
const customConn = document.getElementById('custom-conn');

useAppDb.addEventListener('change', function () {
    customConn.style.display = this.checked ? 'none' : '';
    // Clear custom fields when switching back to app DB
    if (this.checked) {
        customConn.querySelectorAll('input').forEach(i => { if (i.type !== 'password') i.value = ''; });
    }
});

// Preview
document.getElementById('preview-btn').addEventListener('click', async function () {
    const query = document.getElementById('db_query').value.trim();
    if (!query) { alert('Enter a SQL query first.'); return; }

    const status = document.getElementById('preview-status');
    status.textContent = 'Running…';
    this.disabled = true;

    const formData = new FormData();
    formData.append('query', query);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    if (!useAppDb.checked) {
        ['db_host','db_port','db_database','db_username','db_password'].forEach(name => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el) formData.append(name, el.value);
        });
    }

    try {
        const res  = await fetch('{{ route('documents.db-preview') }}', { method: 'POST', body: formData });
        const json = await res.json();

        if (!json.success) { status.textContent = ''; alert('Error: ' + json.error); return; }

        const { columns, rows, total } = json.data;
        status.textContent = '';

        document.getElementById('preview-meta').textContent =
            `Showing ${rows.length} of ${total} total records  |  Columns: ${columns.join(', ')}`;

        const thead = '<thead><tr>' + columns.map(c => `<th>${esc(c)}</th>`).join('') + '</tr></thead>';
        const tbody = '<tbody>' + rows.map(row =>
            '<tr>' + columns.map(c => `<td>${esc(row[c] ?? '')}</td>`).join('') + '</tr>'
        ).join('') + '</tbody>';

        document.getElementById('preview-table').innerHTML = thead + tbody;
        document.getElementById('preview-box').style.display = '';

        // Auto-fill title if blank
        const titleEl = document.getElementById('db_title');
        if (!titleEl.value) {
            const match = query.match(/FROM\s+`?(\w+)`?/i);
            if (match) titleEl.value = match[1].replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) + ' Data';
        }
    } catch (e) {
        status.textContent = 'Request failed.';
    } finally {
        this.disabled = false;
    }
});

function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.getElementById('upload-form').addEventListener('submit', function () {
    document.getElementById('submit-btn').disabled = true;
    document.getElementById('loading').style.display = 'inline';
});
</script>
@endpush
