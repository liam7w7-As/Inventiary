<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_branch_id')->index()->constrained('branches');
            $table->foreignId('to_branch_id')->index()->constrained('branches');
            $table->foreignId('product_id')->index()->constrained('products');
            $table->foreignId('requested_by')->index()->constrained('users');
            $table->foreignId('approved_by')->nullable()->index()->constrained('users');
            $table->decimal('quantity', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('stock_transfers'); }
};