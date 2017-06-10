<?php
/**
 * Mapping.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Configuration\Csv;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Mapper\MapperInterface;
use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use League\Csv\Reader;
use Log;

/**
 * Class Mapping
 *
 * @package FireflyIII\Support\Import\Configuration\Csv
 */
class Map implements ConfigurationInterface
{
    /** @var array that holds each column to be mapped by the user */
    private $data = [];
    /** @var array that holds the indexes of those columns (ie. 2, 5, 8) */
    private $indexes = [];
    /** @var  ImportJob */
    private $job;

    /**
     * ConfigurationInterface constructor.
     *
     * @param ImportJob $job
     */
    public function __construct(ImportJob $job)
    {
        $this->job = $job;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    public function getData(): array
    {
        $config = $this->job->configuration;
        $this->getMappableColumns();


        // in order to actually map we also need all possible values from the CSV file.
        $content = $this->job->uploadFileContents();
        /** @var Reader $reader */
        $reader = Reader::createFromString($content);
        $reader->setDelimiter($config['delimiter']);
        $results        = $reader->fetch();
        $validSpecifics = array_keys(config('csv.import_specifics'));

        foreach ($results as $rowIndex => $row) {

            // skip first row?
            if ($rowIndex === 0 && $config['has-headers']) {
                continue;
            }

            // run specifics here:
            // and this is the point where the specifix go to work.
            foreach ($config['specifics'] as $name => $enabled) {

                if (!in_array($name, $validSpecifics)) {
                    throw new FireflyException(sprintf('"%s" is not a valid class name', $name));
                }
                $class = config('csv.import_specifics.' . $name);
                /** @var SpecificInterface $specific */
                $specific = app($class);

                // it returns the row, possibly modified:
                $row = $specific->run($row);
            }

            //do something here
            foreach ($indexes as $index) { // this is simply 1, 2, 3, etc.
                if (!isset($row[$index])) {
                    // don't really know how to handle this. Just skip, for now.
                    continue;
                }
                $value = $row[$index];
                if (strlen($value) > 0) {

                    // we can do some preprocessing here,
                    // which is exclusively to fix the tags:
                    if (!is_null($data[$index]['preProcessMap'])) {
                        /** @var PreProcessorInterface $preProcessor */
                        $preProcessor           = app($data[$index]['preProcessMap']);
                        $result                 = $preProcessor->run($value);
                        $data[$index]['values'] = array_merge($data[$index]['values'], $result);

                        Log::debug($rowIndex . ':' . $index . 'Value before preprocessor', ['value' => $value]);
                        Log::debug($rowIndex . ':' . $index . 'Value after preprocessor', ['value-new' => $result]);
                        Log::debug($rowIndex . ':' . $index . 'Value after joining', ['value-complete' => $data[$index]['values']]);


                        continue;
                    }

                    $data[$index]['values'][] = $value;
                }
            }
        }
        foreach ($data as $index => $entry) {
            $data[$index]['values'] = array_unique($data[$index]['values']);
        }

        return $data;
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
        return true;
    }

    /**
     * @param string $column
     *
     * @return MapperInterface
     */
    private function createMapper(string $column): MapperInterface
    {
        $mapperClass = config('csv.import_roles.' . $column . '.mapper');
        $mapperName  = sprintf('\\FireflyIII\\Import\Mapper\\%s', $mapperClass);
        /** @var MapperInterface $mapper */
        $mapper = new $mapperName;

        return $mapper;
    }

    /**
     * @return bool
     */
    private function getMappableColumns(): bool
    {
        $config = $this->job->configuration;

        /**
         * @var int  $index
         * @var bool $mustBeMapped
         */
        foreach ($config['column-do-mapping'] as $index => $mustBeMapped) {
            $column    = $this->validateColumnName($config['column-roles'][$index] ?? '_ignore');
            $shouldMap = $this->shouldMapColumn($column, $mustBeMapped);
            if ($shouldMap) {


                // create configuration entry for this specific column and add column to $this->data array for later processing.
                $this->data[$index] = [
                    'name'          => $column,
                    'index'         => $index,
                    'options'       => $this->createMapper($column)->getMap(),
                    'preProcessMap' => $this->getPreProcessorName($column),
                    'values'        => [],
                ];
            }
        }

        return true;

    }

    /**
     * @param string $column
     *
     * @return string
     */
    private function getPreProcessorName(string $column): string
    {
        $name            = '';
        $hasPreProcess   = config('csv.import_roles.' . $column . '.pre-process-map');
        $preProcessClass = config('csv.import_roles.' . $column . '.pre-process-mapper');

        if (!is_null($hasPreProcess) && $hasPreProcess === true && !is_null($preProcessClass)) {
            $name = sprintf('\\FireflyIII\\Import\\MapperPreProcess\\%s', $preProcessClass);
        }

        return $name;
    }

    /**
     * @param string $column
     * @param bool   $mustBeMapped
     *
     * @return bool
     */
    private function shouldMapColumn(string $column, bool $mustBeMapped): bool
    {
        $canBeMapped = config('csv.import_roles.' . $column . '.mappable');

        return ($canBeMapped && $mustBeMapped);
    }

    /**
     * @param string $column
     *
     * @return string
     * @throws FireflyException
     */
    private function validateColumnName(string $column): string
    {
        // is valid column?
        $validColumns = array_keys(config('csv.import_roles'));
        if (!in_array($column, $validColumns)) {
            throw new FireflyException(sprintf('"%s" is not a valid column.', $column));
        }

        return $column;
    }
}