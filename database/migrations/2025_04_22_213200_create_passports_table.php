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
        Schema::create('passports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('passport_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('father_name');
            $table->string('mother_name');
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->string('nationality');
            $table->enum('gender', ['male', 'female']);
            $table->string('national_number')->unique();
            $table->enum('passport_type', ['regular', 'urgent']);
            $table->integer('num_dependents')->default(0);
            $table->string('identity_front');
            $table->string('identity_back');
            $table->boolean('has_old_passport')->default(false);

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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('passports');
        Schema::enableForeignKeyConstraints();
    }
};