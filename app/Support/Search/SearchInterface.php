<?php
/**
 * SearchInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Search;

use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface SearchInterface
 *
 * @package FireflyIII\Support\Search
 */
interface SearchInterface
{
    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchAccounts(array $words): Collection;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchBudgets(array $words): Collection;

    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchCategories(array $words): Collection;

    /**
     *
     * @param array $words
     *
     * @return Collection
     */
    public function searchTags(array $words): Collection;

    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchTransactions(array $words): Collection;
}
