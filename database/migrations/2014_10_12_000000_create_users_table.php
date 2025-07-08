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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 50)->unique();
            $table->string('password', 70);
            $table->string('phone', 50)->unique();
            $table->enum('role', ['user', 'admin', 'super_admin', 'driver'])->default('user');
            $table->Integer('age')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('haj');
        Schema::dropIfExists('ticket_requests');
        Schema::dropIfExists('visa');
        Schema::dropIfExists('passports');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('users');
    }
};
