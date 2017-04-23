<?php
/**
 * RuleFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;

/**
 * Class RuleFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class RuleFormRequest extends Request
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
    public function getRuleData(): array
    {
        return [
            'title'               => $this->string('title'),
            'active'              => $this->boolean('active'),
            'trigger'             => $this->string('trigger'),
            'description'         => $this->string('description'),
            'rule-triggers'       => $this->get('rule-trigger'),
            'rule-trigger-values' => $this->get('rule-trigger-value'),
            'rule-trigger-stop'   => $this->get('rule-trigger-stop'),
            'rule-actions'        => $this->get('rule-action'),
            'rule-action-values'  => $this->get('rule-action-value'),
            'rule-action-stop'    => $this->get('rule-action-stop'),
            'stop_processing'     => $this->boolean('stop_processing'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var RuleGroupRepositoryInterface $repository */
        $repository    = app(RuleGroupRepositoryInterface::class);
        $validTriggers = array_keys(config('firefly.rule-triggers'));
        $validActions  = array_keys(config('firefly.rule-actions'));

        // some actions require text:
        $contextActions = join(',', config('firefly.rule-actions-text'));

        $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title';
        if (!is_null($repository->find(intval($this->get('id')))->id)) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title,' . intval($this->get('id'));
        }

        $rules = [
            'title'                => $titleRule,
            'description'          => 'between:1,5000',
            'stop_processing'      => 'boolean',
            'rule_group_id'        => 'required|belongsToUser:rule_groups',
            'trigger'              => 'required|in:store-journal,update-journal',
            'rule-trigger.*'       => 'required|in:' . join(',', $validTriggers),
            'rule-trigger-value.*' => 'required|min:1|ruleTriggerValue',
            'rule-action.*'        => 'required|in:' . join(',', $validActions),
        ];
        // since Laravel does not support this stuff yet, here's a trick.
        for ($i = 0; $i < 10; $i++) {
            $rules['rule-action-value.' . $i] = 'required_if:rule-action.' . $i . ',' . $contextActions . '|ruleActionValue';
        }

        return $rules;
    }
}
