@extends('layouts.app')
@section('title', $document->title)

@section('content')
<div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem;">
    <a href="{{ route('documents.index') }}" style="color:#64748b; text-decoration:none; font-size:.875rem;">&#8592; Documents</a>
    <h1 style="margin-bottom:0;">{{ $document->title }}</h1>
    <span class="badge badge-blue">{{ $document->chunk_count }} chunks</span>
</div>

<div class="card">
    <h2>Chunks</h2>
    <p style="color:#64748b; font-size:.85rem; margin-bottom:1rem;">
        Each chunk is indexed with MySQL FULLTEXT (BM25) for fast keyword retrieval.
    </p>
    @foreach($chunks as $chunk)
    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:.85rem; margin-bottom:.65rem;">
        <div style="font-size:.75rem; font-weight:600; color:#64748b; margin-bottom:.4rem;">
            Chunk #{{ $chunk->chunk_index }}
        </div>
        <p style="font-size:.875rem; line-height:1.6; color:#334155;">{{ $chunk->content }}</p>
    </div>
    @endforeach
</div>
@endsection
