<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('branch_id')->index()->constrained('branches');
            $table->foreignId('cash_register_id')->nullable()->index()->constrained('cash_registers');
            $table->foreignId('client_id')->nullable()->index()->constrained('clients');
            $table->foreignId('user_id')->index()->constrained('users');
            $table->foreignId('pre_sale_id')->nullable()->index()->constrained('pre_sales');
            $table->enum('sale_type', ['direct', 'presale'])->default('direct');
            $table->enum('payment_type', ['cash', 'credit', 'transfer', 'other']);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->string('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->index()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('sales'); }
};