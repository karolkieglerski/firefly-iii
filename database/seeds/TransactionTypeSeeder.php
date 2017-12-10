<?php
declare(strict_types=1);

use FireflyIII\Models\TransactionType;
use Illuminate\Database\Seeder;

/**
 * Class TransactionTypeSeeder
 */
class TransactionTypeSeeder extends Seeder
{
    public function run()
    {
        TransactionType::create(['type' => TransactionType::WITHDRAWAL]);
        TransactionType::create(['type' => TransactionType::DEPOSIT]);
        TransactionType::create(['type' => TransactionType::TRANSFER]);
        TransactionType::create(['type' => TransactionType::OPENING_BALANCE]);
        TransactionType::create(['type' => TransactionType::RECONCILIATION]);
    }
}
