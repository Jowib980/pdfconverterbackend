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
            $table->string('payer_email');
            $table->string('payer_id');
            $table->string('payer_name');
            $table->string('plan_type');
            $table->string('plan_amount');
            $table->string('transaction_id');
            $table->string('transaction_status');
            $table->date('payment_date');
            $table->string('gateway');
            $table->string('currency');
            $table->text('raw_response');
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
