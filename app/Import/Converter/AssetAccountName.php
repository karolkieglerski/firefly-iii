<?php
/**
 * AssetAccountName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;

/**
 * Class AssetAccountName
 *
 * @package FireflyIII\Import\Converter
 */
class AssetAccountName extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Account
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using AssetAccountName', ['value' => $value]);

        if (strlen($value) === 0) {
            $this->setCertainty(0);

            return new Account;
        }

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);


        if (isset($this->mapping[$value])) {
            Log::debug('Found account in mapping. Should exist.', ['value' => $value, 'map' => $this->mapping[$value]]);
            $account = $repository->find(intval($this->mapping[$value]));
            if (!is_null($account->id)) {
                Log::debug('Found account by ID', ['id' => $account->id]);
                $this->setCertainty(100);

                return $account;
            }
        }

        // not mapped? Still try to find it first:
        $account = $repository->findByName($value, [AccountType::ASSET]);
        if (!is_null($account->id)) {
            Log::debug('Found asset account by name', ['value' => $value, 'id' => $account->id]);

            return $account;
        }


        $account = $repository->store(
            ['name'   => $value, 'iban' => null, 'openingBalance' => 0, 'user' => $this->user->id, 'accountType' => 'asset', 'virtualBalance' => 0,
             'active' => true]
        );

        if (is_null($account->id)) {
            $this->setCertainty(0);
            Log::info('Could not store new asset account by name', $account->getErrors()->toArray());

            return new Account;
        }

        $this->setCertainty(100);

        Log::debug('Created new asset account ', ['name' => $account->name, 'id' => $account->id]);

        return $account;


    }
}
