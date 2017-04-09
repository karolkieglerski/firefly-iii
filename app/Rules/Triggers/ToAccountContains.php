<?php
/**
 * ToAccountContains.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Rules\Triggers;

use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class ToAccountContains
 *
 * @package FireflyIII\Rules\Triggers
 */
final class ToAccountContains extends AbstractTrigger implements TriggerInterface
{

    /**
     * A trigger is said to "match anything", or match any given transaction,
     * when the trigger value is very vague or has no restrictions. Easy examples
     * are the "AmountMore"-trigger combined with an amount of 0: any given transaction
     * has an amount of more than zero! Other examples are all the "Description"-triggers
     * which have hard time handling empty trigger values such as "" or "*" (wild cards).
     *
     * If the user tries to create such a trigger, this method MUST return true so Firefly III
     * can stop the storing / updating the trigger. If the trigger is in any way restrictive
     * (even if it will still include 99.9% of the users transactions), this method MUST return
     * false.
     *
     * @param null $value
     *
     * @return bool
     */
    public static function willMatchEverything($value = null)
    {
        if (!is_null($value)) {
            $res = strval($value) === '';
            if ($res === true) {
                Log::error(sprintf('Cannot use %s with "" as a value.', self::class));
            }

            return $res;
        }
        Log::error(sprintf('Cannot use %s with a null value.', self::class));

        return true;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function triggered(TransactionJournal $journal): bool
    {
        $toAccountName = '';

        /** @var Account $account */
        foreach ($journal->destinationAccountList() as $account) {
            $toAccountName .= strtolower($account->name);
        }

        $search = strtolower($this->triggerValue);
        $strpos = strpos($toAccountName, $search);

        if (!($strpos === false)) {

            Log::debug(sprintf('RuleTrigger ToAccountContains for journal #%d: "%s" contains "%s", return true.', $journal->id, $toAccountName, $search));

            return true;
        }

        Log::debug(
            sprintf(
                'RuleTrigger ToAccountContains for journal #%d: "%s" does not contain "%s", return false.',
                $journal->id, $toAccountName, $search
            )
        );

        return false;
    }
}
