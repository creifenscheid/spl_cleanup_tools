<?php

namespace CReifenscheid\CleanupTools\Utility;

use CReifenscheid\CleanupTools\Service\ConfigurationService;

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
 * Class LocalizationUtility
 *
 * @package CReifenscheid\CleanupTools\Utility
 * @author  C. Reifenscheid
 */
class LocalizationUtility
{
    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param string     $key The key from the LOCAL_LANG array for which to return the value.
     * @param array|null $arguments
     *
     * @return string|null
     */
    public static function translate(string $key, array $arguments = null) : ?string
    {
        $configurationService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConfigurationService::class);

        $localizationPaths = $configurationService->getLocalizationFilePaths();

        foreach ($localizationPaths as $localizationPath) {
            if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($key, 'LLL:')) {
                $localizationPath = "LLL:" . $localizationPath;

                $localizationValue = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($localizationPath . ':' . $key, null, $arguments);
            } else {
                $localizationValue = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, null, $arguments);
            }

            if (!empty($localizationValue)) {
                return $localizationValue;
            }
        }

        return null;
    }
}
