<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('financial_year_id')->constrained()->restrictOnDelete();
            $table->string('bill_number');
            $table->foreignUuid('party_id')->constrained('parties')->restrictOnDelete();
            $table->date('bill_date');
            $table->date('due_date')->nullable();
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

            $table->unique(['business_id', 'financial_year_id', 'bill_number'], 'purchase_bills_number_unique');
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'party_id']);
            $table->index(['business_id', 'bill_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_bills');
    }
};
