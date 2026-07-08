<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->integer('chunk_index');
            $table->text('content');
            $table->json('embedding')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'chunk_index']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_chunks');
    }
};
