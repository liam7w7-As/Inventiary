<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('branch_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index()->constrained('branches');
            $table->foreignId('product_id')->index()->constrained('products');
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['branch_id', 'product_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('branch_products'); }
};