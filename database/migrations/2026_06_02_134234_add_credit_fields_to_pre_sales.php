<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pre_sales', function (Blueprint $table) {
            $table->enum('payment_type', ['cash', 'credit'])->default('cash')->after('notes');
            $table->boolean('requires_approval')->default(false)->after('payment_type');
            $table->integer('credit_installments')->nullable()->after('requires_approval');
            $table->integer('credit_period_days')->nullable()->after('credit_installments');
            $table->integer('approved_installments')->nullable()->after('credit_period_days');
            $table->integer('approved_period_days')->nullable()->after('approved_installments');
            $table->text('credit_notes')->nullable()->after('approved_period_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_sales', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'requires_approval',
                'credit_installments',
                'credit_period_days',
                'approved_installments',
                'approved_period_days',
                'credit_notes'
            ]);
        });
    }
};
