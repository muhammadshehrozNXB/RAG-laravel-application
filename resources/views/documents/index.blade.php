@extends('layouts.app')
@section('title', 'Documents')

@section('content')
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
    <h1 style="margin-bottom:0;">Documents</h1>
    <a href="{{ route('documents.create') }}" class="btn btn-primary">+ Upload Document</a>
</div>

@if($documents->isEmpty())
<div class="card" style="color:#64748b; font-size:.9rem; text-align:center; padding:2.5rem;">
    No documents yet. <a href="{{ route('documents.create') }}" style="color:#2563eb;">Upload your first document</a>.
</div>
@else
<div class="card" style="padding:0; overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Chunks</th>
                <th>File</th>
                <th>Uploaded</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $doc)
            <tr>
                <td>
                    <a href="{{ route('documents.show', $doc) }}" style="color:#2563eb; text-decoration:none; font-weight:500;">
                        {{ $doc->title }}
                    </a>
                </td>
                <td><span class="badge badge-blue">{{ $doc->chunk_count }}</span></td>
                <td style="color:#64748b; font-size:.8rem;">{{ $doc->original_filename ?? '—' }}</td>
                <td style="color:#64748b; font-size:.8rem;">{{ $doc->created_at->diffForHumans() }}</td>
                <td style="text-align:right;">
                    <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                          onsubmit="return confirm('Delete this document and all its chunks?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="padding:.3rem .75rem; font-size:.8rem;">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
