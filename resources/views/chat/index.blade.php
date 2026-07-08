@extends('layouts.app')
@section('title', 'Chat')

@section('content')
<h1>Ask a Question</h1>

<div class="card">
    <form method="POST" action="{{ route('chat.ask') }}" id="chat-form">
        @csrf
        <div class="form-group">
            <label for="question">Your Question</label>
            <textarea
                name="question"
                id="question"
                placeholder="What would you like to know from your documents?"
                rows="3"
            >{{ old('question', $question ?? '') }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary" id="submit-btn">
            Ask
        </button>
    </form>
</div>

@isset($answer)
<div class="card">
    <h2>Answer</h2>
    <p style="line-height:1.7; white-space:pre-wrap;">{{ $answer }}</p>
</div>

@if(!empty($sources))
<div class="card">
    <h2>Sources ({{ count($sources) }})</h2>
    @foreach($sources as $source)
    <div class="source-card">
        <div class="source-title">
            {{ $source['document_title'] }}
            <span class="badge badge-blue" style="margin-left:.5rem;">score: {{ $source['score'] }}</span>
            <span class="badge badge-gray">chunk #{{ $source['chunk_index'] }}</span>
        </div>
        <div class="excerpt">{{ $source['excerpt'] }}</div>
    </div>
    @endforeach
</div>
@endif
@endisset

@if(!isset($answer))
<div class="card" style="color:#64748b; font-size:.9rem; text-align:center; padding:2rem;">
    Upload documents via the <a href="{{ route('documents.create') }}" style="color:#2563eb;">Documents</a> page,
    then ask questions here.
</div>
@endif
@endsection

@push('scripts')
<script>
document.getElementById('chat-form').addEventListener('submit', function() {
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = 'Thinking…';
});
</script>
@endpush
