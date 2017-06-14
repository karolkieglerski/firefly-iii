<?php
/**
 * ImportCurrency.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


class ImportCurrency
{
    private $code   = [];
    private $symbol = [];
    private $name   = [];
    private $id     = [];

    /**
     * @param array $code
     */
    public function setCode(array $code)
    {
        $this->code = $code;
    }

    /**
     * @param array $symbol
     */
    public function setSymbol(array $symbol)
    {
        $this->symbol = $symbol;
    }

    /**
     * @param array $name
     */
    public function setName(array $name)
    {
        $this->name = $name;
    }

    /**
     * @param array $id
     */
    public function setId(array $id)
    {
        $this->id = $id;
    }


}