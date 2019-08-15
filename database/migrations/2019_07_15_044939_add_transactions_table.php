<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary()->unique();
            $table->unsignedBigInteger('from_id');
            $table->foreign('from_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('to_address');
            $table->string('to_name');
            $table->string('to_email');
            // precision needs to be high enough; safer to just use a string rather than decimal
            $table->string('value');
            $table->json('receipt_list');
            $table->string('tx_hash', 64)->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
