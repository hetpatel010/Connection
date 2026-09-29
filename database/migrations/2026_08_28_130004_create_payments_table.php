<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('financial_year_id')->constrained()->restrictOnDelete();
            $table->string('payment_number');
            $table->string('payment_type');
            $table->foreignUuid('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->date('payment_date');
            $table->string('payment_mode')->default('cash');
            $table->foreignUuid('account_id')->constrained('accounts')->restrictOnDelete();
            $table->bigInteger('amount')->default(0);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->foreignUuid('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'financial_year_id', 'payment_number'], 'payments_number_unique');
            $table->index(['business_id', 'payment_type', 'status']);
            $table->index(['business_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
