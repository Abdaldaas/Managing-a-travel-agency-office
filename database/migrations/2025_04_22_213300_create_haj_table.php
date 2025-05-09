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
        Schema::create('haj', function (Blueprint $table) {
            $table->id();
            $table->string('package_type');
            $table->decimal('total_price', 10, 2);
            $table->date('departure_date');
            $table->date('return_date');
            $table->time('takeoff_time');
            $table->time('landing_time');

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
        Schema::dropIfExists('haj');
    }
};