<?php
/**
 * BillTransformer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class BillTransformer
 */
class BillTransformer extends TransformerAbstract
{
    /** @var ParameterBag */
    protected $parameters;

    /** @var BillRepositoryInterface */
    private $repository;

    /**
     * BillTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
        $this->repository = app(BillRepositoryInterface::class);
    }

    /**
     * Transform the bill.
     *
     * @param Bill $bill
     *
     * @return array
     */
    public function transform(Bill $bill): array
    {
        $paidData = $this->paidData($bill);
        $payDates = $this->payDates($bill);
        $this->repository->setUser($bill->user);
        $data = [
            'id'                  => (int)$bill->id,
            'updated_at'          => $bill->updated_at->toAtomString(),
            'created_at'          => $bill->created_at->toAtomString(),
            'name'                => $bill->name,
            'currency_id'         => $bill->transaction_currency_id,
            'currency_code'       => $bill->transactionCurrency->code,
            'currency_symbol'     => $bill->transactionCurrency->symbol,
            'amount_min'          => round((float)$bill->amount_min, 2),
            'amount_max'          => round((float)$bill->amount_max, 2),
            'date'                => $bill->date->format('Y-m-d'),
            'repeat_freq'         => $bill->repeat_freq,
            'skip'                => (int)$bill->skip,
            'automatch'           => $bill->automatch,
            'active'              => $bill->active,
            'attachments_count'   => $bill->attachments()->count(),
            'pay_dates'           => $payDates,
            'notes'               => $this->repository->getNoteText($bill),
            'paid_dates'          => $paidData['paid_dates'],
            'next_expected_match' => $paidData['next_expected_match'],
            'links'               => [
                [
                    'rel' => 'self',
                    'uri' => '/bills/' . $bill->id,
                ],
            ],
        ];

        return $data;

    }

    /**
     * Returns the latest date in the set, or start when set is empty.
     *
     * @param Collection $dates
     * @param Carbon     $default
     *
     * @return Carbon
     */
    protected function lastPaidDate(Collection $dates, Carbon $default): Carbon
    {
        if (0 === $dates->count()) {
            return $default; // @codeCoverageIgnore
        }
        $latest = $dates->first();
        /** @var Carbon $date */
        foreach ($dates as $date) {
            if ($date->gte($latest)) {
                $latest = $date;
            }
        }

        return $latest;
    }

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether or not it is there already, is not relevant.
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return \Carbon\Carbon
     */
    protected function nextDateMatch(Bill $bill, Carbon $date): Carbon
    {
        $start = clone $bill->date;
        while ($start < $date) {
            $start = app('navigation')->addPeriod($start, $bill->repeat_freq, $bill->skip);
        }

        return $start;
    }

    /**
     * Get the data the bill was paid and predict the next expected match.
     *
     * @param Bill $bill
     *
     * @return array
     */
    protected function paidData(Bill $bill): array
    {
        Log::debug(sprintf('Now in paidData for bill #%d', $bill->id));
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            Log::debug('parameters are NULL, return empty array');

            return [
                'paid_dates'          => [],
                'next_expected_match' => null,
            ];
        }

        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser($bill->user);
        $set = $repository->getPaidDatesInRange($bill, $this->parameters->get('start'), $this->parameters->get('end'));
        Log::debug(sprintf('Count %d entries in getPaidDatesInRange()', $set->count()));
        $simple = $set->map(
            function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );

        // calculate next expected match:
        $lastPaidDate = $this->lastPaidDate($set, $this->parameters->get('start'));
        $nextMatch    = clone $bill->date;
        while ($nextMatch < $lastPaidDate) {
            $nextMatch = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        }
        $end          = app('navigation')->addPeriod($nextMatch, $bill->repeat_freq, $bill->skip);
        $journalCount = $repository->getPaidDatesInRange($bill, $nextMatch, $end)->count();
        if ($journalCount > 0) {
            $nextMatch = clone $end;
        }

        return [
            'paid_dates'          => $simple->toArray(),
            'next_expected_match' => $nextMatch->format('Y-m-d'),
        ];
    }

    /**
     * @param Bill $bill
     *
     * @return array
     */
    protected function payDates(Bill $bill): array
    {
        if (null === $this->parameters->get('start') || null === $this->parameters->get('end')) {
            return [];
        }
        $set          = new Collection;
        $currentStart = clone $this->parameters->get('start');
        while ($currentStart <= $this->parameters->get('end')) {
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);
            // If nextExpectedMatch is after end, we continue:
            if ($nextExpectedMatch > $this->parameters->get('end')) {
                break;
            }
            // add to set
            $set->push(clone $nextExpectedMatch);
            $nextExpectedMatch->addDay();
            $currentStart = clone $nextExpectedMatch;
        }
        $simple = $set->map(
            function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );

        return $simple->toArray();
    }
}
