<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('financial_year_id')->constrained()->restrictOnDelete();
            $table->string('journal_number');
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->string('status')->default('posted');
            $table->string('source_type')->nullable();
            $table->uuid('source_id')->nullable();
            $table->foreignUuid('reversal_of_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignUuid('reversed_by_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignUuid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'financial_year_id', 'journal_number']);
            $table->index(['business_id', 'financial_year_id', 'transaction_date']);
            $table->index(['business_id', 'status']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
