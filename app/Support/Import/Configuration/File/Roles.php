<?php
/**
 * Roles.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Import\Configuration\File;

use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use League\Csv\Reader;
use League\Csv\Statement;
use Log;

/**
 * Class Roles.
 */
class Roles implements ConfigurationInterface
{
    /**
     * @var array
     */
    private $data = [];
    /** @var ImportJob */
    private $job;

    /** @var string */
    private $warning = '';

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     * @throws \League\Csv\Exception
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getData(): array
    {
        $config                = $this->job->configuration;
        $content               = $this->job->uploadFileContents();
        $headers               = [];
        $offset                = 0;
        // create CSV reader.
        $reader = Reader::createFromString($content);
        $reader->setDelimiter($config['delimiter']);

        if ($config['has-headers']) {
            $offset = 1;
            // get headers:
            $stmt    = (new Statement)->limit(1)->offset(0);
            $records = $stmt->process($reader);
            $headers = $records->fetchOne();
        }
        // example rows:
        $stmt = (new Statement)->limit(intval(config('csv.example_rows', 5)))->offset($offset);
        // set data:
        $roles = $this->getRoles();
        asort($roles);
        $this->data = [
            'examples' => [],
            'roles'    => $roles,
            'total'    => 0,
            'headers'  => $headers,
        ];

        $records = $stmt->process($reader);
        foreach ($records as $row) {
            $row                 = $this->processSpecifics($row);
            $count               = count($row);
            $this->data['total'] = $count > $this->data['total'] ? $count : $this->data['total'];
            $this->processRow($row);
        }

        $this->updateColumCount();
        $this->makeExamplesUnique();

        return $this->data;
    }

    /**
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string
    {
        return $this->warning;
    }

    /**
     * @param ImportJob $job
     *
     * @return ConfigurationInterface
     */
    public function setJob(ImportJob $job): ConfigurationInterface
    {
        $this->job = $job;

        return $this;
    }

    /**
     * Store the result.
     *
     * @param array $data
     *
     * @return bool
     */
    public function storeConfiguration(array $data): bool
    {
        Log::debug('Now in storeConfiguration of Roles.');
        $config = $this->job->configuration;
        $count  = $config['column-count'];
        for ($i = 0; $i < $count; ++$i) {
            $role                            = $data['role'][$i] ?? '_ignore';
            $mapping                         = isset($data['map'][$i]) && $data['map'][$i] === '1' ? true : false;
            $config['column-roles'][$i]      = $role;
            $config['column-do-mapping'][$i] = $mapping;
            Log::debug(sprintf('Column %d has been given role %s', $i, $role));
        }

        $this->job->configuration = $config;
        $this->job->save();

        $this->ignoreUnmappableColumns();
        $this->setRolesComplete();
        $this->isMappingNecessary();

        return true;
    }

    /**
     * @return array
     */
    private function getRoles(): array
    {
        $roles = [];
        foreach (array_keys(config('csv.import_roles')) as $role) {
            $roles[$role] = trans('import.column_' . $role);
        }

        return $roles;
    }

    /**
     * @return bool
     */
    private function ignoreUnmappableColumns(): bool
    {
        $config = $this->job->configuration;
        $count  = $config['column-count'];
        for ($i = 0; $i < $count; ++$i) {
            $role    = $config['column-roles'][$i] ?? '_ignore';
            $mapping = $config['column-do-mapping'][$i] ?? false;

            if ('_ignore' === $role && true === $mapping) {
                $mapping = false;
                Log::debug(sprintf('Column %d has type %s so it cannot be mapped.', $i, $role));
            }
            $config['column-do-mapping'][$i] = $mapping;
        }

        $this->job->configuration = $config;
        $this->job->save();

        return true;
    }

    /**
     * @return bool
     */
    private function isMappingNecessary()
    {
        $config     = $this->job->configuration;
        $count      = $config['column-count'];
        $toBeMapped = 0;
        for ($i = 0; $i < $count; ++$i) {
            $mapping = $config['column-do-mapping'][$i] ?? false;
            if (true === $mapping) {
                ++$toBeMapped;
            }
        }
        Log::debug(sprintf('Found %d columns that need mapping.', $toBeMapped));
        if (0 === $toBeMapped) {
            // skip setting of map, because none need to be mapped:
            $config['column-mapping-complete'] = true;
            $this->job->configuration          = $config;
            $this->job->save();
        }

        return true;
    }

    /**
     * make unique example data.
     */
    private function makeExamplesUnique(): bool
    {
        foreach ($this->data['examples'] as $index => $values) {
            $this->data['examples'][$index] = array_unique($values);
        }

        return true;
    }

    /**
     * @param array $row
     *
     * @return bool
     */
    private function processRow(array $row): bool
    {
        foreach ($row as $index => $value) {
            $value = trim($value);
            if (strlen($value) > 0) {
                $this->data['examples'][$index][] = $value;
            }
        }

        return true;
    }

    /**
     * run specifics here:
     * and this is the point where the specifix go to work.
     *
     * @param array $row
     *
     * @return array
     */
    private function processSpecifics(array $row): array
    {
        $names = array_keys($this->job->configuration['specifics'] ?? []);
        foreach ($names as $name) {
            /** @var SpecificInterface $specific */
            $specific = app('FireflyIII\Import\Specifics\\' . $name);
            $row      = $specific->run($row);
        }

        return $row;
    }

    /**
     * @return bool
     */
    private function setRolesComplete(): bool
    {
        $config    = $this->job->configuration;
        $count     = $config['column-count'];
        $assigned  = 0;
        $hasAmount = false;
        for ($i = 0; $i < $count; ++$i) {
            $role = $config['column-roles'][$i] ?? '_ignore';
            if ('_ignore' !== $role) {
                ++$assigned;
            }
            if (in_array($role, ['amount', 'amount_credit', 'amount_debit'])) {
                $hasAmount = true;
            }
        }
        if ($assigned > 0 && $hasAmount) {
            $config['column-roles-complete'] = true;
            $this->job->configuration        = $config;
            $this->job->save();
            $this->warning = '';
        }
        if (0 === $assigned || !$hasAmount) {
            $this->warning = strval(trans('import.roles_warning'));
        }

        return true;
    }

    /**
     * @return bool
     */
    private function updateColumCount(): bool
    {
        $config                   = $this->job->configuration;
        $count                    = $this->data['total'];
        $config['column-count']   = $count;
        $this->job->configuration = $config;
        $this->job->save();

        return true;
    }
}
