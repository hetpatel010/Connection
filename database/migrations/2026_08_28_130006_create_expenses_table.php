<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('financial_year_id')->constrained()->restrictOnDelete();
            $table->string('expense_number');
            $table->date('expense_date');
            $table->foreignUuid('expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignUuid('payment_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignUuid('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->bigInteger('amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->string('description');
            $table->string('reference')->nullable();
            $table->string('status')->default('draft');
            $table->foreignUuid('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'financial_year_id', 'expense_number'], 'expenses_number_unique');
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
