<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['title', 'original_filename', 'content', 'chunk_count'];

    public function chunks()
    {
        return $this->hasMany(DocumentChunk::class)->orderBy('chunk_index');
    }
}
