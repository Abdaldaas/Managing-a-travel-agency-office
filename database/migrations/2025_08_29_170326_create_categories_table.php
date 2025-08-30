<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    
        DB::table('categories')->insert([
            ['name' => 'by bus', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'by fly', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
    
    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
