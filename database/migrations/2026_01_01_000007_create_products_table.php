<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->index()->constrained('categories');
            $table->foreignId('supplier_id')->nullable()->index()->constrained('suppliers');
            $table->enum('product_type', ['unit', 'box']);
            $table->integer('units_per_box')->nullable();
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('sale_price', 12, 2);
            $table->decimal('box_discount', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('has_inventory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};