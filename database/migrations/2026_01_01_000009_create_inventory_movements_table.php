<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index()->constrained('branches');
            $table->foreignId('product_id')->index()->constrained('products');
            $table->foreignId('user_id')->index()->constrained('users');
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'transfer_in', 'transfer_out']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->integer('reference_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_movements'); }
};