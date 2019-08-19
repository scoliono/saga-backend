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
            /* copy of address required since users can always change what
               addr they send orders with later on. however, the same cannot be said
               for their real names or emails */
            $table->string('from_address');
            $table->string('to_address');
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
