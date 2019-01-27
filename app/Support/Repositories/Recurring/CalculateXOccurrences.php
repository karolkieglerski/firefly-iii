<?php
declare(strict_types=1);

namespace FireflyIII\Support\Repositories\Recurring;


use Carbon\Carbon;

/**
 * Class CalculateXOccurrences
 */
trait CalculateXOccurrences
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Calculates the number of daily occurrences for a recurring transaction, starting at the date, until $count is reached. It will skip
     * over $skipMod -1 recurrences.
     *
     * @param Carbon $date
     * @param int    $count
     * @param int    $skipMod
     *
     * @return array
     */
    protected function getXDailyOccurrences(Carbon $date, int $count, int $skipMod): array
    {
        $return   = [];
        $mutator  = clone $date;
        $total    = 0;
        $attempts = 0;
        while ($total < $count) {
            $mutator->addDay();
            if (0 === $attempts % $skipMod) {
                $return[] = clone $mutator;
                $total++;
            }
            $attempts++;
        }

        return $return;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * Calculates the number of monthly occurrences for a recurring transaction, starting at the date, until $count is reached. It will skip
     * over $skipMod -1 recurrences.
     *
     * @param Carbon $date
     * @param int    $count
     * @param int    $skipMod
     * @param string $moment
     *
     * @return array
     */
    protected function getXMonthlyOccurrences(Carbon $date, int $count, int $skipMod, string $moment): array
    {
        $return   = [];
        $mutator  = clone $date;
        $total    = 0;
        $attempts = 0;
        $mutator->addDay(); // always assume today has passed.
        $dayOfMonth = (int)$moment;
        if ($mutator->day > $dayOfMonth) {
            // day has passed already, add a month.
            $mutator->addMonth();
        }

        while ($total < $count) {
            $domCorrected = min($dayOfMonth, $mutator->daysInMonth);
            $mutator->day = $domCorrected;
            if (0 === $attempts % $skipMod) {
                $return[] = clone $mutator;
                $total++;
            }
            $attempts++;
            $mutator->endOfMonth()->addDay();
        }

        return $return;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Calculates the number of NDOM occurrences for a recurring transaction, starting at the date, until $count is reached. It will skip
     * over $skipMod -1 recurrences.
     *
     * @param Carbon $date
     * @param int    $count
     * @param int    $skipMod
     * @param string $moment
     *
     * @return array
     */
    protected function getXNDomOccurrences(Carbon $date, int $count, int $skipMod, string $moment): array
    {
        $return   = [];
        $total    = 0;
        $attempts = 0;
        $mutator  = clone $date;
        $mutator->addDay(); // always assume today has passed.
        $mutator->startOfMonth();
        // this feels a bit like a cop out but why reinvent the wheel?
        $counters   = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth',];
        $daysOfWeek = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',];
        $parts      = explode(',', $moment);

        while ($total < $count) {
            $string    = sprintf('%s %s of %s %s', $counters[$parts[0]], $daysOfWeek[$parts[1]], $mutator->format('F'), $mutator->format('Y'));
            $newCarbon = new Carbon($string);
            if (0 === $attempts % $skipMod) {
                $return[] = clone $newCarbon;
                $total++;
            }
            $attempts++;
            $mutator->endOfMonth()->addDay();
        }

        return $return;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * Calculates the number of weekly occurrences for a recurring transaction, starting at the date, until $count is reached. It will skip
     * over $skipMod -1 recurrences.
     *
     * @param Carbon $date
     * @param int    $count
     * @param int    $skipMod
     * @param string $moment
     *
     * @return array
     */
    protected function getXWeeklyOccurrences(Carbon $date, int $count, int $skipMod, string $moment): array
    {
        $return   = [];
        $total    = 0;
        $attempts = 0;
        $mutator  = clone $date;
        // monday = 1
        // sunday = 7
        $mutator->addDay(); // always assume today has passed.
        $dayOfWeek = (int)$moment;
        if ($mutator->dayOfWeekIso > $dayOfWeek) {
            // day has already passed this week, add one week:
            $mutator->addWeek();
        }
        // today is wednesday (3), expected is friday (5): add two days.
        // today is friday (5), expected is monday (1), subtract four days.
        $dayDifference = $dayOfWeek - $mutator->dayOfWeekIso;
        $mutator->addDays($dayDifference);

        while ($total < $count) {
            if (0 === $attempts % $skipMod) {
                $return[] = clone $mutator;
                $total++;
            }
            $attempts++;
            $mutator->addWeek();
        }

        return $return;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * Calculates the number of yearly occurrences for a recurring transaction, starting at the date, until $count is reached. It will skip
     * over $skipMod -1 recurrences.
     *
     * @param Carbon $date
     * @param int    $count
     * @param int    $skipMod
     * @param string $moment
     *
     * @return array
     */
    protected function getXYearlyOccurrences(Carbon $date, int $count, int $skipMod, string $moment): array
    {
        $return     = [];
        $mutator    = clone $date;
        $total      = 0;
        $attempts   = 0;
        $date       = new Carbon($moment);
        $date->year = $mutator->year;
        if ($mutator > $date) {
            $date->addYear();
        }
        $obj = clone $date;
        while ($total < $count) {
            if (0 === $attempts % $skipMod) {
                $return[] = clone $obj;
                $total++;
            }
            $obj->addYears(1);
            $attempts++;
        }

        return $return;

    }
}