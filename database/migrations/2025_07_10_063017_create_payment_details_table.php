<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('payer_email')->nullable();
            $table->string('payer_id')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('plan_type')->nullable();;
            $table->string('plan_amount')->nullable();;
            $table->string('transaction_id')->nullable();;
            $table->string('transaction_status')->nullable();;
            $table->date('payment_date')->nullable();;
            $table->string('gateway')->nullable();;
            $table->string('currency')->nullable();;
            $table->text('raw_response')->nullable();;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};
