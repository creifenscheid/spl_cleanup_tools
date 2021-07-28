<?php

namespace CReifenscheid\CleanupTools\Service\CleanupService;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * *************************************************************
 *
 * Copyright notice
 *
 * (c) 2021 C. Reifenscheid
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */

/**
 * Class CorruptMMRelationsService
 *
 * Detects and removes corrupt mm-relations, where either uid_local or uid_foreign are not existing or the entry is existing multiple times
 *
 * @package CReifenscheid\CleanupTools\Service\CleanupService
 */
class CorruptMMRelationsService extends AbstractCleanupService
{
    /**
     * Executes the command
     *
     * @return \TYPO3\CMS\Core\Messaging\FlashMessage
     */
    public function execute() : FlashMessage
    {
        // find all mm-relations
        $mmRelations = $this->findAllMMRelations();

        // no mm-relations are existing
        if (!$mmRelations) {
            $message = $this->addLLLMessage('messages.noRelations.CorruptMMRelationsService');

            return $this->createFlashMessage(FlashMessage::INFO, $message);
        }

        // get all corrupt mm-relations
        $corruptMMRelations = $this->getAllCorruptMMRelations($mmRelations);

        if ($this->dryRun) {
            $affectedTables = $corruptMMRelations['statistics']['tables'];
            $affectedRelations = $corruptMMRelations['statistics']['relations'];

            if ($affectedRelations > 0) {
                $message = $this->addLLLMessage('messages.dryRun.CorruptMMRelationsService', [$affectedRelations, $affectedTables]);
            } else {
                $message = $this->addLLLMessage('messages.nothingToDo.CorruptMMRelationsService');
            }

            return $this->createFlashMessage(FlashMessage::INFO, $message);
        }

        // if their are corrupt mm-relations
        if ($corruptMMRelations['statistics']['relations'] > 0) {
            // remove statistics
            unset($corruptMMRelations['statistics']);

            // delete corrupt mm-relations
            return $this->deleteCorruptMMRelations($corruptMMRelations);
        }

        $message = $this->addLLLMessage('messages.nothingToDo.CorruptMMRelationsService');
        return $this->createFlashMessage(FlashMessage::INFO, $message);
    }

    /**
     * Finds all mm-relations within the TCA
     *
     * @return array|null
     */
    private function findAllMMRelations() : ?array
    {
        $mmRelations = [];

        foreach ($GLOBALS['TCA'] as $table => $config) {
            foreach ($config['columns'] as $column) {

                /**
                 * To prevent duplicate entries, it is necessary to identify the local part and the foreign part of a relation
                 * Therefore some configurations are checked
                 * MM: indicates a mm-relation
                 * foreign_table: indicates the counterpart of the relation
                 * MM_opposite_field: indicates that the current table is the foreign part of the relation
                 *
                 * By skipping columns with this property, only local parts of an relation are stored, so there are no duplicates
                 */
                if (
                    $column['config']['MM'] &&
                    $column['config']['foreign_table'] &&
                    !$column['config']['MM_opposite_field']
                ) {
                    $mmRelations[$column['config']['MM']] = [
                        'mm_table' => $column['config']['MM'],
                        'local_table' => $table,
                        'foreign_table' => $column['config']['foreign_table']
                    ];
                }
            }
        }

        return !empty($mmRelations) ? $mmRelations : null;
    }

    /**
     * Returns all corrupt mm-relations
     *
     * @param array $mmRelations
     *
     * @return array|null
     */
    private function getAllCorruptMMRelations(array $mmRelations) : ?array
    {
        $corruptMMRelations = [
            'statistics' => [
                'tables' => 0,
                'relations' => 0
            ]
        ];

        // loop through all relations
        foreach ($mmRelations as $mmRelation) {

            // define needed tables
            $mmTable = $mmRelation['mm_table'];
            $localTable = $mmRelation['local_table'];
            $foreignTable = $mmRelation['foreign_table'];
            $tableIsAffected = false;

            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mmTable);

            // get all entries of the mm table
            $entries = $queryBuilder->select('*')->from($mmTable)->execute()->fetchAll();

            // no entries - no checks - skip
            if (empty($entries)) {
                continue;
            }

            // loop through all entries
            foreach ($entries as $key => $entry) {

                // check for duplicates
                // SeppToDo: check if there is a nicer way to check on duplicate array entries based on a few key - 2 in this case
                foreach ($entries as $compareKey => $compareEntry) {
                    if (
                        ($key !== $compareKey) &&
                        $entry['uid_local'] === $compareEntry['uid_local'] &&
                        $entry['uid_foreign'] === $compareEntry['uid_foreign']
                    ) {
                        $corruptMMRelations[$mmTable][] = $entry;

                        // set flag that table is affected
                        $tableIsAffected = true;

                        // increment counter
                        ++$corruptMMRelations['statistics']['relations'];

                        // remove compare key from entries to prevent duplicates
                        unset($entries[$compareKey]);
                    }
                }

                // check for missing uid_local counterpart
                if (!$this->checkIfElementExists($localTable, $entry['uid_local'])) {
                    $corruptMMRelations[$mmTable][] = $entry;

                    // set flag that table is affected
                    $tableIsAffected = true;

                    // increment counter
                    ++$corruptMMRelations['statistics']['relations'];
                }

                // check for missing uid_foreign counterpart
                if (!$this->checkIfElementExists($foreignTable, $entry['uid_foreign'])) {
                    $corruptMMRelations[$mmTable][] = $entry;

                    // set flag that table is affected
                    $tableIsAffected = true;

                    // increment counter
                    ++$corruptMMRelations['statistics']['relations'];
                }

                // remove key to prevent duplicates in further loops
                unset($entries[$key]);
            }

            if ($tableIsAffected) {
                ++$corruptMMRelations['statistics']['tables'];
            }
        }

        return !empty($corruptMMRelations) ? $corruptMMRelations : null;
    }

    /**
     * Check if a element with given uid exists in given table
     *
     * @param string $table
     * @param int    $uid
     *
     * @return bool
     */
    private function checkIfElementExists(string $table, int $uid) : bool
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $countQueryBuilder */
        $countQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $countQueryBuilder
            ->select('uid')
            ->from($table)
            ->where($countQueryBuilder->expr()->eq('uid', $countQueryBuilder->createNamedParameter($uid)));

        return $countQueryBuilder->execute()->rowCount() > 0;

    }

    /**
     * Remove corrupt mm-relations
     *
     * @param array $relations
     *
     * @return \TYPO3\CMS\Core\Messaging\FlashMessage
     */
    private function deleteCorruptMMRelations(array $relations) : FlashMessage
    {
        // remove corrupt entries
        foreach ($relations as $mmTable => $entries) {

            foreach ($entries as $entry) {
                /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $deleteQueryBuilder */
                $deleteQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mmTable);

                // define storage for where expressions
                $whereExpressions = [];

                /**
                 * loop through properties and create corresponding where clause
                 *
                 * It is important to create a where clause for every property.
                 * Due to missing uid of relation entries,
                 * there is no other possibility to delete exactly this specific entry
                 */
                foreach ($entry as $property => $propertyValue) {
                    $whereExpressions[] = $deleteQueryBuilder->expr()->eq($property, $deleteQueryBuilder->createNamedParameter($propertyValue));
                }

                // delete from table with given where expressions
                $deleteQueryBuilder
                    ->delete($mmTable)
                    ->where(...$whereExpressions)
                    ->execute();
            }

            // add log message with more information of table and entries
            if (count($entries) > 1) {
                $this->addLLLMessage('messages.deleteFromTable.CorruptMMRelationsService', [count($entries), $mmTable]);
            } else {
                $this->addLLLMessage('messages.deletesFromTable.CorruptMMRelationsService', [count($entries), $mmTable]);
            }
        }

        return $this->createFlashMessage();
    }
}
