<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop embedding column if it exists
        if (Schema::hasColumn('document_chunks', 'embedding')) {
            DB::statement('ALTER TABLE document_chunks DROP COLUMN embedding');
        }

        // Add MySQL FULLTEXT index for BM25-style retrieval
        DB::statement('ALTER TABLE document_chunks ADD FULLTEXT INDEX ft_chunk_content (content)');
    }

    public function down()
    {
        DB::statement('ALTER TABLE document_chunks DROP INDEX ft_chunk_content');
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding JSON NULL');
    }
};
