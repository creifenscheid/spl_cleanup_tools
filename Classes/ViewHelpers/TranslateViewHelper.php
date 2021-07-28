<?php

namespace CReifenscheid\CleanupTools\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

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
 * Class TranslateViewHelper
 *
 * @package CReifenscheid\CleanupTools\ViewHelpers
 * @author  C. Reifenscheid
 */
class TranslateViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    use \TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('key', 'string', 'Localization key');
        $this->registerArgument('arguments', 'array', 'Arguments to be replaced in the resulting string');
    }

    /**
     * Return array element by key.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $key = $arguments['key'];
        $translateArguments = $arguments['arguments'];

        if ((string)$key === '') {
            throw new Exception('An argument "key" has to be provided', 1351584844);
        }

        return \CReifenscheid\CleanupTools\Utility\LocalizationUtility::translate($key, $translateArguments);
    }
}