<?php
/**
 * TagFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;

/**
 * Class TagFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class TagFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function collectTagData() :array
    {
        if ($this->get('setTag') == 'true') {
            $latitude  = $this->get('latitude');
            $longitude = $this->get('longitude');
            $zoomLevel = $this->get('zoomLevel');
        } else {
            $latitude  = null;
            $longitude = null;
            $zoomLevel = null;
        }
        $date = $this->get('date') ?? '';

        $data = [
            'tag'         => $this->get('tag'),
            'date'        => strlen($date) > 0 ? new Carbon($date) : null,
            'description' => $this->get('description') ?? '',
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'zoomLevel'   => $zoomLevel,
            'tagMode'     => $this->get('tagMode'),
        ];

        return $data;


    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $idRule     = '';
        $tagRule    = 'required|min:1|uniqueObjectForUser:tags,tag';
        if (!is_null($repository->find(intval($this->get('id')))->id)) {
            $idRule  = 'belongsToUser:tags';
            $tagRule = 'required|min:1|uniqueObjectForUser:tags,tag,' . $this->get('id');
        }

        return [
            'tag'         => $tagRule,
            'id'          => $idRule,
            'description' => 'min:1',
            'date'        => 'date',
            'latitude'    => 'numeric|min:-90|max:90',
            'longitude'   => 'numeric|min:-90|max:90',
            'zoomLevel'   => 'numeric|min:0|max:80',
            'tagMode'     => 'required|in:nothing,balancingAct,advancePayment',
        ];
    }
}
