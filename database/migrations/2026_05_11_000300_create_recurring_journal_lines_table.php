<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();

            $table->index('recurring_journal_id');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_journal_lines');
    }
};
