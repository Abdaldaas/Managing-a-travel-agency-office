<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rejection_reasons')) {
            Schema::create('rejection_reasons', function (Blueprint $table) {
                $table->id();
                $table->string('reason');
                $table->enum('request_type', ['visa', 'passport', 'ticket', 'taxi', 'general','haj'])->default('general');
                $table->unsignedBigInteger('request_id')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('rejection_reasons');
    }
};