<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id');
            $table->datetime('date');
            $table->string('subject', 512);
            $table->string('to', 1024);
            $table->string('from', 512);
            $table->text('raw_header')->nullable();
            $table->longtext('raw_body')->nullable();
            $table->json('status')->nullable();
            $table->string('unidade', 10)->nullable();
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
        Schema::dropIfExists('emails');
    }
}
