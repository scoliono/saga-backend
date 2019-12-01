<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePendingTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('from_name');
            $table->string('from_email');

            $table->unsignedBigInteger('to_id');
            $table->foreign('to_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('to_address');

            $table->string('value');
            $table->json('receipt_list');
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
        Schema::dropIfExists('pending_transactions');
    }
}
