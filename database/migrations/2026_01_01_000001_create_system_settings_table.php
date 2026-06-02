<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('alias');
            $table->string('logo')->nullable();
            $table->string('activity');
            $table->string('currency')->default('BOB');
            $table->time('admin_hour_start');
            $table->time('admin_hour_end');
            $table->time('encargado_hour_start');
            $table->time('encargado_hour_end');
            $table->enum('print_format', ['58mm', '80mm', 'A4'])->default('58mm');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('system_settings'); }
};