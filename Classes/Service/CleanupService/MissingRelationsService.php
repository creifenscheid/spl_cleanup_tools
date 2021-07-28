<?php
declare(strict_types = 1);
namespace CReifenscheid\CleanupTools\Service\CleanupService;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * *************************************************************
 *
 * Copyright notice
 *
 * (c) 2020 C. Reifenscheid
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
 * Finds references and soft-references to
 * - records which are marked as deleted (e.g. still in the system as reminder)
 * - offline versions (references should never point to offline versions)
 * - non-existing records (records which have been deleted not via DataHandler)
 *
 * @see \TYPO3\CMS\Lowlevel\Command\MissingRelationsCommand::class
 *
 * @package CReifenscheid\CleanupTools\Service\CleanupService
 * @author C. Reifenscheid
 */
class MissingRelationsService extends AbstractCleanupService
{
    /**
     * Setting this option automatically updates the reference index
     *
     * @var bool
     */
    private $updateRefIndex = false;

    /**
     * @param boolean $updateRefIndex
     * @return void
     */
    public function setUpdateRefIndex(bool $updateRefIndex) : void
    {
        $this->updateRefIndex = $updateRefIndex;
    }

    /**
     * @var ConnectionPool
     */
    private $connectionPool;
    
    /**
     * Executes the command to
     * - optionally update the reference index (to have clean data)
     * - find data in sys_refindex (softrefs and regular references) where the reference points to a non-existing record or offline version
     * - remove these files if --dry-run is not set (not possible for refindexes)
     *
     * @return \TYPO3\CMS\Core\Messaging\FlashMessage
     */
    public function execute(): \TYPO3\CMS\Core\Messaging\FlashMessage
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        
        // Update the reference index
        if ($this->updateRefIndex) {
            $this->updateReferenceIndex();
        }
        
        $results = $this->findRelationsToNonExistingRecords();
        
        // Display soft references to non-existing records
        if (count($results['nonExistingRecordsInSoftReferenceRelations'])) {
            $this->addMessage('Found ' . count($results['nonExistingRecordsInSoftReferenceRelations']) .
                ' non-existing records that are still being soft-referenced in the following locations.' .
                'These relations cannot be removed automatically and need manual repair.');
            
            foreach ($results['nonExistingRecordsInSoftReferenceRelations'] as $fileName => $references) {
                $message = 'File: ' . $fileName . ' -> References: ';
                
                foreach ($references as $hash => $recordReference) {
                    $message = $recordReference .' (Hash: ' . $hash . ')';
                }
                
                $this->addMessage($message);
            }
        }
        
        // Display soft references to offline version records
        // These records are offline versions having a pid=-1 and references should never occur directly to their uids.
        if (count($results['offlineVersionRecordsInSoftReferenceRelations'])) {
            $this->addMessage('Found ' . count($results['offlineVersionRecordsInSoftReferenceRelations']) .
                ' soft-references pointing to offline versions, which should never be referenced directly.'.
                'These relations cannot be removed automatically and need manual repair.');
            
            foreach ($results['offlineVersionRecordsInSoftReferenceRelations'] as $fileName => $references) {
                $message = 'File: ' . $fileName . ' -> References: ';
                
                foreach ($references as $hash => $recordReference) {
                    $message = $recordReference .' (Hash: ' . $hash . ')';
                }
                
                $this->addMessage($message);
            }
        }
        
        // Display references to deleted records
        // These records are deleted with a flag but references are still pointing at them.
        // Keeping the references is useful if you undelete the referenced records later, otherwise the references
        // are lost completely when the deleted records are flushed at some point. Notice that if those records listed
        // are themselves deleted (marked with "DELETED") it is not a problem.
        if (count($results['deletedRecords'])) {
            $this->addMessage('Found ' . count($results['deletedRecords']) . ' references pointing to deleted records.',
                'Keeping the references is useful if you undelete the referenced records later, otherwise the references' .
                'are lost completely when the deleted records are flushed at some point. Notice that if those records listed' .
                'are themselves deleted (marked with "DELETED") it is not a problem.');
            
            foreach ($results['deletedRecords'] as $fileName => $references) {
                $message = 'File: ' . $fileName . ' -> References: ';
                
                foreach ($references as $hash => $recordReference) {
                    $message = $recordReference .' (Hash: ' . $hash . ')';
                }
                
                $this->addMessage($message);
            }
        }
        
        // soft references which link to deleted records
        if (count($results['deletedRecordsInSoftReferenceRelations'])) {
            $this->addMessage('Found ' . count($results['deletedRecords']) . ' references pointing to deleted records.'.
                'Found ' . count($results['deletedRecordsInSoftReferenceRelations']) . ' soft references pointing  to deleted records.'.
                'Keeping the references is useful if you undelete the referenced records later, otherwise the references' .
                'are lost completely when the deleted records are flushed at some point. Notice that if those records listed' .
                'are themselves deleted (marked with "DELETED") it is not a problem.');
            
            foreach ($results['deletedRecordsInSoftReferenceRelations'] as $fileName => $references) {
                $message = 'File: ' . $fileName . ' -> References: ';
                
                foreach ($references as $hash => $recordReference) {
                    $message = $recordReference .' (Hash: ' . $hash . ')';
                }
                
                $this->addMessage($message);
            }
        }
        
        // Find missing references
        if (count($results['offlineVersionRecords']) || count($results['nonExistingRecords'])) {
            
            if ($this->dryRun) {
                $message = 'Found ' . count($results['nonExistingRecords']) . ' references to non-existing records ' . 'and ' . count($results['offlineVersionRecords']) . ' references directly linked to offline versions.';
                $this->addMessage($message);
                return $this->createFlashMessage(\TYPO3\CMS\Core\Messaging\FlashMessage::INFO, $message);
            } else {
                $this->removeReferencesToMissingRecords($results['offlineVersionRecords'], $results['nonExistingRecords']);
                $message = 'All references were updated accordingly.';
                $this->addMessage($message);
                return $this->createFlashMessage(\TYPO3\CMS\Core\Messaging\FlashMessage::OK, $message);
            }
        } else {
            $message = 'Nothing to do, no missing relations found. Everything is in place.';
            $this->addMessage($message);
            return $this->createFlashMessage(\TYPO3\CMS\Core\Messaging\FlashMessage::INFO, $message);
        }
    }
    
    /**
     * Function to update the reference index
     * - if the option --update-refindex is set, do it
     * - otherwise, if in interactive mode (not having -n set), ask the user
     * - otherwise assume everything is fine
     */
    protected function updateReferenceIndex()
    {
        $referenceIndex = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ReferenceIndex::class);
        $referenceIndex->updateIndex(false);
    }
    
    /**
     * Find relations pointing to non-existing records (in managed references or soft-references)
     *
     * @return array an array of records within sys_refindex
     */
    protected function findRelationsToNonExistingRecords(): array
    {
        $deletedRecords = [];
        $deletedRecordsInSoftReferenceRelations = [];
        $nonExistingRecords = [];
        $nonExistingRecordsInSoftReferenceRelations = [];
        $offlineVersionRecords = [];
        $offlineVersionRecordsInSoftReferenceRelations = [];
        
        // Select DB relations from reference table
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $rowIterator = $queryBuilder
        ->select('ref_uid', 'ref_table', 'softref_key', 'hash', 'tablename', 'recuid', 'field', 'flexpointer', 'deleted')
        ->from('sys_refindex')
        ->where(
            $queryBuilder->expr()->neq('ref_table', $queryBuilder->createNamedParameter('_FILE', \PDO::PARAM_STR)),
            $queryBuilder->expr()->gt('ref_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->execute();
            
            $existingRecords = [];
            while ($rec = $rowIterator->fetch()) {
                $isSoftReference = !empty($rec['softref_key']);
                $idx = $rec['ref_table'] . ':' . $rec['ref_uid'];
                // Get referenced record:
                if (!isset($existingRecords[$idx])) {
                    $queryBuilder = $this->connectionPool
                    ->getQueryBuilderForTable($rec['ref_table']);
                    $queryBuilder->getRestrictions()->removeAll();
                    
                    $selectFields = ['uid', 'pid'];
                    if (isset($GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'])) {
                        $selectFields[] = $GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'];
                    }
                    if (BackendUtility::isTableWorkspaceEnabled($rec['ref_table'])) {
                        $selectFields[] = 't3ver_oid';
                        $selectFields[] = 't3ver_wsid';
                    }
                    
                    $existingRecords[$idx] = $queryBuilder
                    ->select(...$selectFields)
                    ->from($rec['ref_table'])
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($rec['ref_uid'], \PDO::PARAM_INT)
                            )
                        )
                        ->execute()
                        ->fetch();
                }
                // Compile info string for location of reference:
                $infoString = $this->formatReferenceIndexEntryToString($rec);
                // Handle missing file:
                if ($existingRecords[$idx]['uid']) {
                    // Record exists, but is a reference to an offline version
                    if ((int)($existingRecords[$idx]['t3ver_oid'] ?? 0) > 0) {
                        if ($isSoftReference) {
                            $offlineVersionRecordsInSoftReferenceRelations[] = $infoString;
                        } else {
                            $offlineVersionRecords[$idx][$rec['hash']] = $infoString;
                        }
                        // reference to a deleted record
                    } elseif (isset($GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete']) && $existingRecords[$idx][$GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete']]) {
                        if ($isSoftReference) {
                            $deletedRecordsInSoftReferenceRelations[] = $infoString;
                        } else {
                            $deletedRecords[] = $infoString;
                        }
                    }
                } else {
                    if ($isSoftReference) {
                        $nonExistingRecordsInSoftReferenceRelations[] = $infoString;
                    } else {
                        $nonExistingRecords[$idx][$rec['hash']] = $infoString;
                    }
                }
            }
            
            return [
                // Non-existing records to which there are references (managed)
                // These references can safely be removed since there is no record found in the database at all.
                'nonExistingRecords' => ArrayUtility::sortByKeyRecursive($nonExistingRecords),
                // Non-existing records to which there are references (softref)
                'nonExistingRecordsInSoftReferenceRelations' => ArrayUtility::sortByKeyRecursive($nonExistingRecordsInSoftReferenceRelations),
                // Offline version records (managed)
                // These records are offline versions having a pid=-1 and references should never occur directly to their uids.
                'offlineVersionRecords' => ArrayUtility::sortByKeyRecursive($offlineVersionRecords),
                // Offline version records (softref)
                'offlineVersionRecordsInSoftReferenceRelations' => ArrayUtility::sortByKeyRecursive($offlineVersionRecordsInSoftReferenceRelations),
                // Deleted-flagged records (managed)
                // These records are deleted with a flag but references are still pointing at them.
                // Keeping the references is useful if you undelete the referenced records later, otherwise the references
                // are lost completely when the deleted records are flushed at some point. Notice that if those records listed
                // are themselves deleted (marked with "DELETED") it is not a problem.
                'deletedRecords' => ArrayUtility::sortByKeyRecursive($deletedRecords),
                // Deleted-flagged records (softref)
                'deletedRecordsInSoftReferenceRelations' => ArrayUtility::sortByKeyRecursive($deletedRecordsInSoftReferenceRelations),
            ];
    }
    
    /**
     * Removes all references to non-existing records or offline versions
     *
     * @param array $offlineVersionRecords Contains the records of offline versions of sys_refindex which need to be removed
     * @param array $nonExistingRecords Contains the records non-existing records of sys_refindex which need to be removed
     */
    protected function removeReferencesToMissingRecords(array $offlineVersionRecords, array $nonExistingRecords) {
            // Remove references to offline records
            foreach ($offlineVersionRecords as $fileName => $references) {
                foreach ($references as $hash => $recordReference) {
                    $sysRefObj = GeneralUtility::makeInstance(ReferenceIndex::class);
                    $error = $sysRefObj->setReferenceValue($hash, null);
                    if ($error) {
                        $this->addMessage('Removing reference in record "' . $recordReference . '" (Hash: ' . $hash . ')' .
                            'ReferenceIndex::setReferenceValue() reported "' . $error . '"'
                        );
                    }
                }
            }
            
            // Remove references to non-existing records
            foreach ($nonExistingRecords as $fileName => $references) {
                foreach ($references as $hash => $recordReference) {
                    $sysRefObj = GeneralUtility::makeInstance(ReferenceIndex::class);
                    $error = $sysRefObj->setReferenceValue($hash, null);
                    if ($error) {
                        $this->addMessage('Removing reference in record "' . $recordReference . '" (Hash: ' . $hash . ')' .
                            'ReferenceIndex::setReferenceValue() reported "' . $error . '"'
                        );
                    }
                }
            }
    }
    
    /**
     * Formats a sys_refindex entry to something readable
     *
     * @param array $record
     * @return string
     */
    protected function formatReferenceIndexEntryToString(array $record): string
    {
        return $record['tablename']
        . ':' . $record['recuid']
        . ':' . $record['field']
        . ($record['flexpointer'] ? ':' . $record['flexpointer'] : '')
        . ($record['softref_key'] ? ':' . $record['softref_key'] . ' (Soft Reference) ' : '')
        . ($record['deleted'] ? ' (DELETED)' : '');
    }
}