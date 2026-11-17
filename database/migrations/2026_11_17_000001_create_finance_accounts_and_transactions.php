<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finance_accounts')) {
            Schema::create('finance_accounts', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->string('type', 24);
                $table->decimal('opening_balance', 12, 2)->default(0);
                $table->date('opening_balance_date');
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('finance_transactions')) {
            Schema::create('finance_transactions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('destination_account_id')->nullable();
                $table->unsignedBigInteger('business_expense_payment_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('type', 16);
                $table->string('description', 255);
                $table->decimal('amount', 12, 2);
                $table->date('transaction_date');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex('finance_accounts', ['is_active', 'name'])) {
            Schema::table('finance_accounts', function (Blueprint $table): void {
                $table->index(['is_active', 'name'], 'finance_accounts_active_name_idx');
            });
        }

        if (! Schema::hasIndex('finance_transactions', ['account_id', 'transaction_date'])) {
            Schema::table('finance_transactions', function (Blueprint $table): void {
                $table->index(['account_id', 'transaction_date'], 'finance_tx_account_date_idx');
            });
        }

        if (! Schema::hasIndex('finance_transactions', ['destination_account_id', 'transaction_date'])) {
            Schema::table('finance_transactions', function (Blueprint $table): void {
                $table->index(['destination_account_id', 'transaction_date'], 'finance_tx_dest_date_idx');
            });
        }

        if (! Schema::hasIndex('finance_transactions', ['business_expense_payment_id'], 'unique')) {
            Schema::table('finance_transactions', function (Blueprint $table): void {
                $table->unique('business_expense_payment_id', 'finance_tx_payment_unique');
            });
        }

        $this->ensureForeignKey('account_id', 'finance_accounts', 'finance_tx_account_fk', 'restrict');
        $this->ensureForeignKey('destination_account_id', 'finance_accounts', 'finance_tx_destination_fk', 'restrict');
        $this->ensureForeignKey('business_expense_payment_id', 'business_expense_payments', 'finance_tx_payment_fk', 'null');
        $this->ensureForeignKey('user_id', 'users', 'finance_tx_user_fk', 'null');
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_accounts');
    }

    private function ensureForeignKey(string $column, string $referencedTable, string $name, string $onDelete): void
    {
        foreach (Schema::getForeignKeys('finance_transactions') as $foreignKey) {
            if ($foreignKey['columns'] === [$column] && $foreignKey['foreign_table'] === $referencedTable) {
                return;
            }
        }

        Schema::table('finance_transactions', function (Blueprint $table) use ($column, $referencedTable, $name, $onDelete): void {
            $foreign = $table->foreign($column, $name)->references('id')->on($referencedTable);
            if ($onDelete === 'restrict') {
                $foreign->restrictOnDelete();
            } else {
                $foreign->nullOnDelete();
            }
        });
    }
};
