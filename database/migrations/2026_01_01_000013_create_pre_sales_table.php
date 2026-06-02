<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pre_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index()->constrained('branches');
            $table->foreignId('client_id')->nullable()->index()->constrained('clients');
            $table->foreignId('requested_by')->index()->constrained('users');
            $table->foreignId('approved_by')->nullable()->index()->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected', 'converted'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('pre_sales'); }
};