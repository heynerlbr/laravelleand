<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loans', function (Blueprint $column) {
            $column->id();
            $column->unsignedBigInteger('user_id');
            $column->decimal('amount', 15, 2);
            $column->date('payment_date');
            $column->decimal('interest_rate', 5, 2)->default(24.10);
            $column->decimal('interest_amount', 15, 2);
            $column->decimal('fianza_amount', 15, 2)->nullable();
            $column->decimal('firma_electronica_amount', 15, 2)->nullable();
            $column->decimal('iva_amount', 15, 2);
            $column->decimal('total_to_pay', 15, 2);
            $column->string('status')->default('pending'); // pending, approved, rejected, paid
            $column->timestamps();

            $column->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loans');
    }
}
