<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->index()->constrained('sales');
            $table->foreignId('client_id')->index()->constrained('clients');
            $table->foreignId('branch_id')->index()->constrained('branches');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            $table->date('due_date');
            $table->enum('status', ['active', 'paid', 'overdue'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('credits'); }
};