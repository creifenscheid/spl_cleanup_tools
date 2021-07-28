<?php
namespace CReifenscheid\CleanupTools\Service\CleanupService;

use CReifenscheid\CleanupTools\Utility\LocalizationUtility;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

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
 * Class AbstractCleanupService
 *
 * @packagee CReifenscheid\CleanupTools\Service\CleanupService
 * @author C. Reifenscheid
 */
abstract class AbstractCleanupService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /*
     * dry run
     *
     * @var boolean
     */
    protected $dryRun = true;

    /**
     * log
     *
     * @var \CReifenscheid\CleanupTools\Domain\Model\Log
     */
    protected $log;
    
    /**
     * Execute cleanup process
     *
     * @return \TYPO3\CMS\Core\Messaging\FlashMessage
     */
    abstract public function execute(): \TYPO3\CMS\Core\Messaging\FlashMessage;
    
    /**
     * Returns dry run
     *
     * @return bool
     */
    public function getDryRun(): bool
    {
        return $this->dryRun;
    }
    
    /**
     * Sets dry run
     *
     * @param bool $dryRun
     */
    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    /**
     * Returns log
     *
     * @return \CReifenscheid\CleanupTools\Domain\Model\Log
     */
    public function getLog(): \CReifenscheid\CleanupTools\Domain\Model\Log
    {
        
        // if a log exists, return
        if ($this->log) {
            return $this->log;
        }
        
        // else return new log object
        $log = new \CReifenscheid\CleanupTools\Domain\Model\Log();
        
        // set creation time
        $log->setCrdate(time());

        // set be user if logged in
        if ($GLOBALS['BE_USER']->user['uid']) {
            $log->setCruserId($GLOBALS['BE_USER']->user['uid']);
        }
        
        // set new log object
        $this->setLog($log);
        
        return $log;
    }

    /**
     * Sets log
     *
     * @param \CReifenscheid\CleanupTools\Domain\Model\Log $log
     * @return void
     */
    public function setLog(\CReifenscheid\CleanupTools\Domain\Model\Log $log): void
    {
        $this->log = $log;
    }

    /**
     * Create and add logMessage object
     *
     * @param string|null $message
     */
    protected function addMessage(?string $message): void
    {
        if (!$this->dryRun) {
            $log = $this->getLog();
        
            // create new message
            $newLogMessage = new \CReifenscheid\CleanupTools\Domain\Model\LogMessage();
            $newLogMessage->setLog($log);
            $newLogMessage->setMessage($message);

            // add message to log
            $log->addMessage($newLogMessage);
            
            // save log
            $this->setLog($log);
        }
    }

    /**
     * Create and add logMessage object with localization key
     *
     * @param string     $key
     * @param null|array $arguments
     *
     * @return null|string
     */
    protected function addLLLMessage(string $key, ?array $arguments = null): ?string
    {
        if (!$this->dryRun) {
            $log = $this->getLog();
        
            // create new message
            $newLogMessage = new \CReifenscheid\CleanupTools\Domain\Model\LogMessage();
        
            $newLogMessage->setLog($log);
            $newLogMessage->setLocalLangKey($key);

            if ($arguments) {
                $newLogMessage->setLocalLangArguments($arguments);
            }

            // add message to log
            $log->addMessage($newLogMessage);
            
            // save log
            $this->setLog($log);
        }

        // get localization value to return for further usage, e.g. as flash message
        $message = LocalizationUtility::translate($key, $arguments);

        if (!$message) {
            $this->logger->warning(__CLASS__ . ':: Message could not be localized', ['key' => $key]);
        }

        return $message;
    }

    /**
     * Create flash messsage object
     *
     * @param int $severity
     * @param null|string $message
     * @param null|string $headline
     *
     * @return \TYPO3\CMS\Core\Messaging\FlashMessage
     */
    protected function createFlashMessage(int $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK, string $message = null, $headline = null): \TYPO3\CMS\Core\Messaging\FlashMessage
    {
        // define headline
        $headline = $headline ?: \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:cleanup_tools/Resources/Private/Language/locallang_mod.xlf:messages.fallback.headline', 'CleanupTools');

        // define message
        $message = $message ?: \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:cleanup_tools/Resources/Private/Language/locallang_mod.xlf:messages.success.message', 'CleanupTools');

        // initialize and return flash message object
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $message, $headline, $severity);
    }
}
