<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index()->constrained('branches');
            $table->foreignId('user_id')->index()->constrained('users');
            $table->foreignId('opened_by')->index()->constrained('users');
            $table->decimal('initial_balance', 12, 2);
            $table->decimal('sale_limit', 12, 2)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('cash_registers'); }
};