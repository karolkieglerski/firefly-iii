<?php
/**
 * CurrencyExchangeRateTransformer.php
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


use FireflyIII\Models\CurrencyExchangeRate;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class CurrencyExchangeRateTransformer
 */
class CurrencyExchangeRateTransformer extends TransformerAbstract
{
    /** @var ParameterBag */
    protected $parameters;

    /**
     * PiggyBankEventTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param CurrencyExchangeRate $rate
     *
     * @return array
     */
    public function transform(CurrencyExchangeRate $rate): array
    {
        $data = [
            'id'         => (int)$rate->id,
            'updated_at' => $rate->updated_at->toAtomString(),
            'created_at' => $rate->created_at->toAtomString(),
            'date'       => $rate->date->format('Y-m-d'),
            'rate'       => (float)$rate->rate,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => '/currency_exchange_rates/' . $rate->id,
                ],
            ],
        ];

        return $data;
    }
}
