<?php

namespace CReifenscheid\CleanupTools\Utility;

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
 * Class ConfigurationManagementUtility
 *
 * @package CReifenscheid\CleanupTools\Utility
 * @author  C. Reifenscheid
 */
class ConfigurationManagementUtility
{
    /**
     * Registers cleanup service
     * 
     * @param string $identifier
     * @param string $className
     * @param bool $schedulerTask
     * @param bool $toolbar
     * @param bool $enabled
     *
     * @return void
     */
    public static function addCleanupService (string $identifier, string $className, bool $schedulerTask = true, bool $toolbar = true, bool $enabled = true) : void
    {
        if (!class_exists($className)) {
            $message = sprintf('Class "%s" is not existing', $className);
            throw new \CReifenscheid\CleanupTools\Exception\InvalidCleanupServiceException($message, 2112198401);
        }
        
        if (!is_subclass_of($className, \CReifenscheid\CleanupTools\Service\CleanupService\AbstractCleanupService::class)) {
            $message = sprintf('"%s" does not implement "%s" and is therefor invalid', $className, \CReifenscheid\CleanupTools\Service\CleanupService\AbstractCleanupService::class);
            throw new \CReifenscheid\CleanupTools\Exception\InvalidCleanupServiceException($message, 2112198401);
        }
        
        $configuration = self::getConfiguration();
       
        $configuration['cleanup_services'][$identifier] = [
            'class' => $className,
            'enabled' => $enabled,
            'additionalUsage' => [
                'schedulerTask' => $schedulerTask,
                'toolbar' => $toolbar
            ]
        ];
        
        self::writeConfiguration($configuration);
    }
    
    /**
     * Registers localization file path
     * 
     * @param string $localizationFilePath
     * 
     * @return void
     */
    public static function addLocalizationFilePath(string $localizationFilePath) : void
    {
        $configuration = self::getConfiguration();
        $configuration['localizationFilePaths'][] = $localizationFilePath;
        self::writeConfiguration($configuration);
    }
    
    /**
     * Disable/enable cleanup service
     * 
     * @param string $identifier
     * @param bool $value
     *
     * @return void
     */
    private static function setEnable(string $identifier, bool $value) : void
    {
        self::validateIdentifier($identifier);
    
        $configuration = self::getConfiguration();
       
        $configuration['cleanup_services'][$identifier]['enabled'] =  $value;
            self::writeConfiguration($configuration);
    }
    
    /**
     * Disable/enable additional usage of cleanup service
     * 
     * @param string $identifier
     * @param string $usage
     * @param bool $value
     *
     * @return void
     */
    private static function setAdditionalUsage(string $identifier, string $usage, bool $value) : void
    {
        self::validateIdentifier($identifier);
    
        $configuration = self::getConfiguration();
       
        $configuration['cleanup_services'][$identifier]['additionalUsage'][$usage] =  $value;
            self::writeConfiguration($configuration);
    }
    
    /**
     * Enable/disable cleanup service in scheduler task
     * 
     * @param string $identifier
     * @param bool $value
     *
     * @return void
     */
    public static function setSchedulerTask (string $identifier, bool $value) : void
    {
        self::setAdditionalUsage($identifier, 'schedulerTask', $value);
    }
    
    /**
     * Enable/disable cleanup service in toolbar
     * 
     * @param string $identifier
     * @param bool $value
     *
     * @return void
     */
    public static function setToolbar (string $identifier, bool $value) : void
    {
        self::setAdditionalUsage($identifier, 'toolbar', $value);
    }
    
    /**
     * Remove cleanup service
     * 
     * @param string $identifier
     *
     * @return void
     */
    public static function removeCleanupService (string $identifier) : void
    {
        self::validateIdentifier($identifier);
        
        $configuration = self::getConfiguration();
        
        unset($configuration['cleanup_services'][$identifier]);
        
        self::writeConfiguration($configuration);
    }
    
    /**
     * Returns configuration array within $GLOBALS
     * 
     * @return array
     */
    public static function getConfiguration() : array 
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cleanup_tools']) {
            return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cleanup_tools'];
        }
        
        return [];
    }
    
    /**
     * Writes configuration array
     * 
     * @param array $configuration
     */
    private static function writeConfiguration(array $configuration) : void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cleanup_tools'] = $configuration;
    }
    
    private static function validateIdentifier(string $identifier) : void
    {
        $configuration = self::getConfiguration();
       
        if (!$configuration['cleanup_services'][$identifier]) {
            $message = 'CleanupService with identifier "'. $identifier . '" is not registered.';
            throw new \CReifenscheid\CleanupTools\Exception\NotRegisteredException($message, 2112198402);
        }
    }
}
