<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('passport_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('passport_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['processing', 'pending_payment', 'completed', 'rejected']);
            $table->enum('passport_type', ['regular', 'urgent','express']);
            $table->decimal('price', 8, 2);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('passport_requests');
    }
};