<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type'); // passport_requested, visa_status_updated, etc.
            $table->string('request_type'); // passport, visa, ticket
            $table->unsignedBigInteger('request_id');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'request_type', 'request_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
}
