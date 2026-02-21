<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstallmentsToLoansTable extends Migration
{
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->integer('installments_count')->default(1)->after('total_to_pay');
            $table->string('installment_frequency')->default('monthly')->after('installments_count'); // monthly, biweekly, weekly
        });

        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->integer('installment_number');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending, paid, late
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_installments');
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['installments_count', 'installment_frequency']);
        });
    }
}
