<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSQRLTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sqrl_pubkeys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('public_key')->unique();
            $table->string('vuk');
            $table->string('suk');
            $table->tinyInteger('disabled')->default(0);
            $table->tinyInteger('sqrl_only_allowed')->default(0);
            $table->tinyInteger('hardlock')->default(0);
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('sqrl_nonces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nonce')->unique();
            $table->enum('type', ['auth', 'question']);
            $table->ipAddress('ip_address');
            $table->longText('url');
            $table->longText('can');
            $table->tinyInteger('verified')->default(0);
            $table->longText('question')->nullable();
            $table->tinyInteger('btn_answer')->nullable();
            $table->string('orig_nonce')->nullable();
            $table->bigInteger('sqrl_pubkey_id')->unsigned()->nullable();
            $table->foreign('sqrl_pubkey_id')->references('id')->on('sqrl_pubkeys')->onDelete('cascade');
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
        Schema::dropIfExists('sqrl_nonces');
        Schema::dropIfExists('sqrl_pubkeys');
    }
}
