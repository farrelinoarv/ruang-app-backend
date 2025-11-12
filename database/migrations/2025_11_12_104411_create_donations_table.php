<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donor_name', 191)->default('Seseorang');
            $table->decimal('amount', 15, 2);
            $table->text('message')->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->string('midtrans_order_id', 100)->nullable();
            $table->string('midtrans_transaction_id', 100)->nullable();
            $table->enum('payment_status', ['pending', 'success', 'failed', 'expired'])->default('pending');
            $table->string('transaction_ref', 150)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
