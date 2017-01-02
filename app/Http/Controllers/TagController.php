<?php
/**
 * TagController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Requests\TagFormRequest;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Preferences;
use Session;
use URL;
use View;

/**
 * Class TagController
 *
 * Remember: a balancingAct takes at most one expense and one transfer.
 *           an advancePayment takes at most one expense, infinite deposits and NO transfers.
 *
 *  transaction can only have one advancePayment OR balancingAct.
 *  Other attempts to put in such a tag are blocked.
 *  also show an error when editing a tag and it becomes either
 *  of these two types. Or rather, block editing of the tag.
 *
 * @package FireflyIII\Http\Controllers
 */
class TagController extends Controller
{

    public $tagOptions = [];

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('hideTags', true);

        $this->middleware(
            function ($request, $next) {
                View::share('title', 'Tags');
                View::share('mainTitleIcon', 'fa-tags');
                $this->tagOptions = [
                    'nothing'        => trans('firefly.regular_tag'),
                    'balancingAct'   => trans('firefly.balancing_act'),
                    'advancePayment' => trans('firefly.advance_payment'),
                ];
                View::share('tagOptions', $this->tagOptions);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return View
     */
    public function create(Request $request)
    {
        $subTitle     = trans('firefly.new_tag');
        $subTitleIcon = 'fa-tag';
        $apiKey       = env('GOOGLE_MAPS_API_KEY', '');

        $preFilled = [
            'tagMode' => 'nothing',
        ];
        if (!$request->old('tagMode')) {
            Session::flash('preFilled', $preFilled);
        }
        // put previous url in session if not redirect from store (not "create another").
        if (session('tags.create.fromStore') !== true) {
            Session::put('tags.create.url', URL::previous());
        }
        Session::forget('tags.create.fromStore');
        Session::flash('gaEventCategory', 'tags');
        Session::flash('gaEventAction', 'create');

        return view('tags.create', compact('subTitle', 'subTitleIcon', 'apiKey'));
    }

    /**
     * @param Tag $tag
     *
     * @return View
     */
    public function delete(Tag $tag)
    {
        $subTitle = trans('breadcrumbs.delete_tag', ['tag' => e($tag->tag)]);

        // put previous url in session
        Session::put('tags.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'tags');
        Session::flash('gaEventAction', 'delete');

        return view('tags.delete', compact('tag', 'subTitle'));
    }

    /**
     * @param TagRepositoryInterface $repository
     * @param Tag                    $tag
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TagRepositoryInterface $repository, Tag $tag)
    {

        $tagName = $tag->tag;
        $repository->destroy($tag);

        Session::flash('success', strval(trans('firefly.deleted_tag', ['tag' => e($tagName)])));
        Preferences::mark();

        return redirect(route('tags.index'));
    }

    /**
     * @param Tag $tag
     *
     * @return View
     */
    public function edit(Tag $tag)
    {
        $subTitle     = trans('firefly.edit_tag', ['tag' => $tag->tag]);
        $subTitleIcon = 'fa-tag';
        $apiKey       = env('GOOGLE_MAPS_API_KEY', '');

        /*
         * Default tag options (again)
         */
        $tagOptions = $this->tagOptions;

        /*
         * Can this tag become another type?
         */
        $allowAdvance        = Tag::tagAllowAdvance($tag);
        $allowToBalancingAct = Tag::tagAllowBalancing($tag);

        // edit tag options:
        if ($allowAdvance === false) {
            unset($tagOptions['advancePayment']);
        }
        if ($allowToBalancingAct === false) {
            unset($tagOptions['balancingAct']);
        }


        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('tags.edit.fromUpdate') !== true) {
            Session::put('tags.edit.url', URL::previous());
        }
        Session::forget('tags.edit.fromUpdate');
        Session::flash('gaEventCategory', 'tags');
        Session::flash('gaEventAction', 'edit');

        return view('tags.edit', compact('tag', 'subTitle', 'subTitleIcon', 'tagOptions', 'apiKey'));
    }

    /**
     *
     */
    public function index()
    {
        $title         = 'Tags';
        $mainTitleIcon = 'fa-tags';
        $types         = ['nothing', 'balancingAct', 'advancePayment'];

        // loop each types and get the tags, group them by year.
        $collection = [];
        foreach ($types as $type) {

            /** @var Collection $tags */
            $tags = auth()->user()->tags()->where('tagMode', $type)->orderBy('date', 'ASC')->get();
            $tags = $tags->sortBy(
                function (Tag $tag) {
                    $date = !is_null($tag->date) ? $tag->date->format('Ymd') : '000000';


                    return strtolower($date . $tag->tag);
                }
            );

            /** @var Tag $tag */
            foreach ($tags as $tag) {

                $year           = is_null($tag->date) ? trans('firefly.no_year') : $tag->date->year;
                $monthFormatted = is_null($tag->date) ? trans('firefly.no_month') : $tag->date->formatLocalized($this->monthFormat);

                $collection[$type][$year][$monthFormatted][] = $tag;
            }
        }

        return view('tags.index', compact('title', 'mainTitleIcon', 'types', 'collection'));
    }

    /**
     * @param Request                   $request
     * @param JournalCollectorInterface $collector
     * @param Tag                       $tag
     *
     * @return View
     */
    public function show(Request $request, JournalCollectorInterface $collector, Tag $tag)
    {
        $subTitle     = $tag->tag;
        $subTitleIcon = 'fa-tag';
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);

        // use collector:
        $collector->setAllAssetAccounts()->setLimit($pageSize)->setPage($page)->setTag($tag)->withBudgetInformation()->withCategoryInformation();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('tags/show/' . $tag->id);

        $sum = $journals->sum(
            function (Transaction $transaction) {
                return $transaction->transaction_amount;
            }
        );

        return view('tags.show', compact('tag', 'subTitle', 'subTitleIcon', 'journals', 'sum'));
    }

    /**
     * @param TagFormRequest         $request
     *
     * @param TagRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TagFormRequest $request, TagRepositoryInterface $repository)
    {
        $data = $request->collectTagData();
        $repository->store($data);

        Session::flash('success', strval(trans('firefly.created_tag', ['tag' => e($data['tag'])])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('tags.create.fromStore', true);

            return redirect(route('tags.create'))->withInput();
        }

        // redirect to previous URL.
        return redirect(session('tags.create.url'));

    }

    /**
     * @param TagFormRequest         $request
     * @param TagRepositoryInterface $repository
     * @param Tag                    $tag
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TagFormRequest $request, TagRepositoryInterface $repository, Tag $tag)
    {
        $data = $request->collectTagData();
        $repository->update($tag, $data);

        Session::flash('success', strval(trans('firefly.updated_tag', ['tag' => e($data['tag'])])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('tags.edit.fromUpdate', true);

            return redirect(route('tags.edit', [$tag->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(session('tags.edit.url'));
    }
}
