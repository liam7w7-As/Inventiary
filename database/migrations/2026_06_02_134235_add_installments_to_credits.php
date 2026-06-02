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
        Schema::table('credits', function (Blueprint $table) {
            $table->integer('installments_count')->default(1)->after('status');
            $table->decimal('installment_amount', 12, 2)->default(0)->after('installments_count');
            $table->date('start_date')->nullable()->after('installment_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn(['installments_count', 'installment_amount', 'start_date']);
        });
    }
};
