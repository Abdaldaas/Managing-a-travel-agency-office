<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->string('visa_type');
            $table->decimal('Total_cost', 10, 2);
            $table->enum('Status', ['pending', 'approved', 'rejected']);
            $table->foreignId('Admin_id')->nullable()->constrained('admins')->onDelete('set null');
            $table->string('PassportFile');
            $table->string('PhotoFile');
            $table->foreignId('rejection_reason_id')->nullable()->constrained('rejection_reasons')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visa');
    }
};
