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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('hash')->unique();
            $table->foreignId('user_id')->constrained();
            $table->string('title');
            $table->string('slug');
            $table->string('url');
            $table->text('description')->nullable();
            $table->text('excerpt')->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('visibility');
            $table->foreign('visibility')->references('id')->on('visibilities');
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
        Schema::dropIfExists('links');
    }
};
