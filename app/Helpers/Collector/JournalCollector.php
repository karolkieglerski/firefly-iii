<?php
declare(strict_types = 1);

namespace FireflyIII\Helpers\Collector;


use Carbon\Carbon;
use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 * Maybe this is a good idea after all...
 *
 * Class JournalCollector
 *
 * @package FireflyIII\Helpers\Collector
 */
class JournalCollector
{

    /** @var  int */
    private $count = 0;

    /** @var array */
    private $fields
        = [
            'transaction_journals.id as journal_id',
            'transaction_journals.description',
            'transaction_journals.date',
            'transaction_journals.encrypted',
            //'transaction_journals.transaction_currency_id',
            'transaction_currencies.code as transaction_currency_code',
            //'transaction_currencies.symbol as transaction_currency_symbol',
            'transaction_types.type as transaction_type_type',
            'transaction_journals.bill_id',
            'bills.name as bill_name',
            'transactions.id as id',
            'transactions.amount as transaction_amount',
            'transactions.description as transaction_description',
            'transactions.account_id',
            'transactions.identifier',
            'transactions.transaction_journal_id',
            'accounts.name as account_name',
            'accounts.encrypted as account_encrypted',
            'account_types.type as account_type',

        ];
    /** @var  bool */
    private $joinedCategory = false;
    /** @var  int */
    private $limit;
    /** @var  int */
    private $offset;
    /** @var int */
    private $page = 1;
    /** @var EloquentBuilder */
    private $query;
    /** @var bool */
    private $run = false;
    /** @var User */
    private $user;

    /**
     * JournalCollector constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user  = $user;
        $this->query = $this->startQuery();
    }

    /**
     * @return int
     * @throws FireflyException
     */
    public function count(): int
    {
        if ($this->run === true) {
            throw new FireflyException('Cannot count after run in JournalCollector.');
        }

        $countQuery = clone $this->query;

        // dont need some fields:
        $countQuery->getQuery()->limit      = null;
        $countQuery->getQuery()->offset     = null;
        $countQuery->getQuery()->unionLimit = null;
        $countQuery->getQuery()->groups     = null;
        $countQuery->groupBy('accounts.user_id');
        $this->count = $countQuery->count();

        return $this->count;
    }

    /**
     * @return Collection
     */
    public function getJournals(): Collection
    {
        $this->run = true;
        $set       = $this->query->get($this->fields);

        // loop for decryption.
        $set->each(
            function (Transaction $transaction) {
                $transaction->date        = new Carbon($transaction->date);
                $transaction->description = intval($transaction->encrypted) === 1 ? Crypt::decrypt($transaction->description) : $transaction->description;
                $transaction->bill_name   = !is_null($transaction->bill_name) ? Crypt::decrypt($transaction->bill_name) : '';
            }
        );

        return $set;
    }

    /**
     * It might be worth it to expand this query to include all account information required.
     *
     * @param Collection $accounts
     * @param array      $types
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getJournalsInPeriod(Collection $accounts, array $types, Carbon $start, Carbon $end): Collection
    {
        $accountIds = $accounts->pluck('id')->toArray();
        $query      = Transaction
            ::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_currencies', 'transaction_currencies.id', 'transaction_journals.transaction_currency_id')
            ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
            ->leftJoin('bills', 'bills.id', 'transaction_journals.bill_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->leftJoin('account_types', 'accounts.account_type_id', 'account_types.id')
            ->whereIn('transactions.account_id', $accountIds)
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
            ->where('transaction_journals.user_id', $this->user->id)
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.order', 'ASC')
            ->orderBy('transaction_journals.id', 'DESC');

        if (count($types) > 0) {
            $query->whereIn('transaction_types.type', $types);
        }

        $set = $query->get(
            [
                'transaction_journals.id as journal_id',
                'transaction_journals.description',
                'transaction_journals.date',
                'transaction_journals.encrypted',
                //'transaction_journals.transaction_currency_id',
                'transaction_currencies.code as transaction_currency_code',
                //'transaction_currencies.symbol as transaction_currency_symbol',
                'transaction_types.type as transaction_type_type',
                'transaction_journals.bill_id',
                'bills.name as bill_name',
                'transactions.id as id',
                'transactions.amount as transaction_amount',
                'transactions.description as transaction_description',
                'transactions.account_id',
                'transactions.identifier',
                'transactions.transaction_journal_id',
                'accounts.name as account_name',
                'accounts.encrypted as account_encrypted',
                'account_types.type as account_type',

            ]
        );

        // loop for decryption.
        $set->each(
            function (Transaction $transaction) {
                $transaction->date        = new Carbon($transaction->date);
                $transaction->description = intval($transaction->encrypted) === 1 ? Crypt::decrypt($transaction->description) : $transaction->description;
                $transaction->bill_name   = !is_null($transaction->bill_name) ? Crypt::decrypt($transaction->bill_name) : '';
            }
        );

        return $set;
    }

    /**
     * @return LengthAwarePaginator
     * @throws FireflyException
     */
    public function getPaginatedJournals():LengthAwarePaginator
    {
        if ($this->run === true) {
            throw new FireflyException('Cannot getPaginatedJournals after run in JournalCollector.');
        }
        $this->count();
        $set      = $this->getJournals();
        $journals = new LengthAwarePaginator($set, $this->count, $this->limit, $this->page);

        return $journals;
    }

    /**
     * @param Collection $accounts
     *
     * @return JournalCollector
     */
    public function setAccounts(Collection $accounts): JournalCollector
    {
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->whereIn('transactions.account_id', $accountIds);
        }

        return $this;
    }

    /**
     * @param Category $category
     *
     * @return JournalCollector
     */
    public function setCategory(Category $category): JournalCollector
    {
        if (!$this->joinedCategory) {
            // join some extra tables:
            $this->joinedCategory = true;
            $this->query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $this->query->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id');
        }

        $this->query->where(
            function (EloquentBuilder $q) use ($category) {
                $q->where('category_transaction.category_id', $category->id);
                $q->orWhere('category_transaction_journal.category_id', $category->id);
            }
        );

        return $this;
    }

    /**
     * @param Collection $accounts
     *
     * @return JournalCollector
     */
    public function setDestinationAccounts(Collection $accounts): JournalCollector
    {
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->whereIn('transactions.account_id', $accountIds);
            $this->query->where('transactions.amount', '>', 0);
        }

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return JournalCollector
     */
    public function setLimit(int $limit): JournalCollector
    {
        $this->limit = $limit;
        $this->query->limit($limit);
        Log::debug(sprintf('Set limit to %d', $limit));

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return JournalCollector
     */
    public function setOffset(int $offset): JournalCollector
    {
        $this->offset = $offset;
    }

    /**
     * @param int $page
     *
     * @return JournalCollector
     */
    public function setPage(int $page): JournalCollector
    {
        $this->page = $page;

        if ($page > 0) {
            $page--;
        }
        Log::debug(sprintf('Page is %d', $page));

        if (!is_null($this->limit)) {
            $offset       = ($this->limit * $page);
            $this->offset = $offset;
            $this->query->skip($offset);
            Log::debug(sprintf('Changed offset to %d', $offset));
        }
        if (is_null($this->limit)) {
            Log::debug('The limit is zero, cannot set the page.');
        }

        return $this;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return JournalCollector
     */
    public function setRange(Carbon $start, Carbon $end): JournalCollector
    {
        if ($start <= $end) {
            $this->query->where('transaction_journals.date', '>=', $start->format('Y-m-d'));
            $this->query->where('transaction_journals.date', '<=', $end->format('Y-m-d'));
        }

        return $this;
    }

    /**
     * @param Collection $accounts
     *
     * @return JournalCollector
     */
    public function setSourceAccounts(Collection $accounts): JournalCollector
    {
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $this->query->whereIn('transactions.account_id', $accountIds);
            $this->query->where('transactions.amount', '<', 0);
        }

        return $this;
    }

    /**
     * @param array $types
     *
     * @return JournalCollector
     */
    public function setTypes(array $types): JournalCollector
    {
        if (count($types) > 0) {
            $this->query->whereIn('transaction_types.type', $types);
        }

        return $this;
    }

    /**
     * @return EloquentBuilder
     */
    private function startQuery(): EloquentBuilder
    {

        $query = Transaction
            ::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_currencies', 'transaction_currencies.id', 'transaction_journals.transaction_currency_id')
            ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
            ->leftJoin('bills', 'bills.id', 'transaction_journals.bill_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->leftJoin('account_types', 'accounts.account_type_id', 'account_types.id')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_journals.user_id', $this->user->id)
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.order', 'ASC')
            ->orderBy('transaction_journals.id', 'DESC');

        return $query;

    }

}