<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            $table->enum('type', ['visa', 'ticket', 'passport', 'haj', 'hotel']);
            $table->string('status');
            $table->decimal('price', 10, 2);
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    
    {
        Schema::table('bookings', function (Blueprint $table) 
        {
        $table->dropColumn('stripe_payment_intent_id');
        });
        Schema::dropIfExists('bookings');
    }
};