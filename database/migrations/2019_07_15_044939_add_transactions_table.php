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
            $table->string('id', 24)
                ->unique()
                ->primary();
            $table->unsignedBigInteger('from_id');
            $table->foreign('from_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('to_address');
            // precision needs to be high enough; safer to just use a string rather than decimal
            $table->string('value');
            $table->json('receipt_list');
            $table->boolean('completed')
                ->nullable(false)
                ->default(false);
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
