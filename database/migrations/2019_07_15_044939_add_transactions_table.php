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
            $table->bigIncrements('id');
            $table->unsignedBigInteger('from_id')->nullable(true);
            $table->foreign('from_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            /* copy of address required since users can always change what
               addr they send orders with later on. however, the same cannot be said
               for their real names or emails */
            $table->string('from_address')->nullable(true);

            $table->string('from_name')->nullable(true);
            $table->string('from_email')->nullable(true);

            $table->unsignedBigInteger('to_id');
            $table->foreign('to_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('to_address');

            // precision needs to be high enough; safer to just use a string rather than decimal
            $table->string('value');
            $table->json('receipt_list');

            // Presence of tx_hash indicates the transaction is completed
            $table->string('tx_hash', 66)->nullable(true);

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
