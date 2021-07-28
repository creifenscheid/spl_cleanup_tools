<?php

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 C. Reifenscheid
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Cleanup Tools',
    'description' => 'This extension provides cleanup tools and the possibility to implement cleanup services in your TYPO3 installation.',
    'category' => 'module',
    'author' => 'C. Reifenscheid',
    'version' => '10.0.2',
    'state' => 'alpha',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99'
        ],
        'suggests' => [
            'dashboard' => '10.4.0 - 10.4.99',
            'scheduler' => '10.4.0 - 10.4.99'
        ]
    ],
    'autoload' => [
        'psr-4' => [
            'CReifenscheid\\CleanupTools\\' => 'Classes'
        ]
    ]
];
