<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->index()->constrained('sales');
            $table->foreignId('branch_id')->index()->constrained('branches');
            $table->enum('format', ['58mm', '80mm', 'A4']);
            $table->foreignId('printed_by')->index()->constrained('users');
            $table->timestamp('printed_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('delivery_notes'); }
};