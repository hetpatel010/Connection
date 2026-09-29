<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('financial_year_id')->constrained()->restrictOnDelete();
            $table->string('credit_note_number');
            $table->foreignUuid('source_sales_invoice_id')->constrained('sales_invoices')->restrictOnDelete();
            $table->foreignUuid('party_id')->constrained('parties')->restrictOnDelete();
            $table->date('credit_note_date');
            $table->string('settlement_mode')->default('credit');
            $table->foreignUuid('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->bigInteger('subtotal_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->text('notes')->nullable();
            $table->string('reference')->nullable();
            $table->foreignUuid('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'financial_year_id', 'credit_note_number'], 'credit_notes_number_unique');
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'party_id']);
            $table->index(['business_id', 'credit_note_date']);
            $table->index(['source_sales_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
