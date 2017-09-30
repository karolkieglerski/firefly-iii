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

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

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
    public function collectTagData(): array
    {
        $latitude  = null;
        $longitude = null;
        $zoomLevel = null;

        if ($this->get('tag_position_has_tag') === 'true') {
            $latitude  = $this->string('tag_position_latitude');
            $longitude = $this->string('tag_position_longitude');
            $zoomLevel = $this->integer('tag_position_zoomlevel');
        }

        $data = [
            'tag'         => $this->string('tag'),
            'date'        => $this->date('date'),
            'description' => $this->string('description'),
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'zoomLevel'   => $zoomLevel,
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
            'description' => 'min:1|nullable',
            'date'        => 'date|nullable',
            'latitude'    => 'numeric|min:-90|max:90|nullable',
            'longitude'   => 'numeric|min:-90|max:90|nullable',
            'zoomLevel'   => 'numeric|min:0|max:80|nullable',
        ];
    }
}
