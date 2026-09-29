<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('prefix')->default('');
            $table->foreignUuid('financial_year_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(
                ['business_id', 'document_type', 'financial_year_id'],
                'doc_seq_business_type_year_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
