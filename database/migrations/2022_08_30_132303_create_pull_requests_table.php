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
        Schema::create('pull_requests', function (Blueprint $table) {
            $table->id();
            $table->string('branch');
            $table->string('commit')->unique();
            $table->string('path');
            $table->string('url')->comment('Url Token of the App');
            $table->string('stored_path')->nullable();
            $table->unsignedBigInteger('port')->nullable();
            $table->unsignedBigInteger('ssl_port')->nullable();
            $table->string('token')->unique();
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
        Schema::dropIfExists('pull_requests');
    }
};
