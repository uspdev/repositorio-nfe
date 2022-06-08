<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNfesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nfes', function (Blueprint $table) {
            $table->id();
            $table->decimal('chave', 44, 0);
            $table->longText('xml');
            $table->json('prot'); //xml
            $table->json('emit');
            $table->json('dest');
            $table->json('ide');
            $table->json('consulta');
            $table->text('infadic');
            $table->string('unidade', 10)->nullable();
            $table->integer('ano');
            $table->foreignId('email_id')->nullOnDelete()->nullable()->constrained();
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
        Schema::dropIfExists('nfes');
    }
}
