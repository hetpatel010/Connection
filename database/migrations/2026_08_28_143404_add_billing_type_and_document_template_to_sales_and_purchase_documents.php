<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('billing_type')->default('gst')->after('status');
            $table->string('document_template')->nullable()->after('billing_type');
        });

        Schema::table('purchase_bills', function (Blueprint $table) {
            $table->string('billing_type')->default('gst')->after('status');
            $table->string('document_template')->nullable()->after('billing_type');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_bills', function (Blueprint $table) {
            $table->dropColumn(['billing_type', 'document_template']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['billing_type', 'document_template']);
        });
    }
};
