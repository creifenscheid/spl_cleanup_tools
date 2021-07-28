<?php

defined('TYPO3_MODE') or die();


(function ($extKey) {
    
    // CLEANUP SERVICE REGISTRATION
    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addCleanupService(
        'cleanFlexFormsService',
        \CReifenscheid\CleanupTools\Service\CleanupService\CleanFlexFormsService::class
    );
    
    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addCleanupService(
        'deletedRecordsService',
        \CReifenscheid\CleanupTools\Service\CleanupService\DeletedRecordsService::class
    );
    
    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addCleanupService(
        'lostFilesService',
        \CReifenscheid\CleanupTools\Service\CleanupService\LostFilesService::class
    );
    
    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addCleanupService(
        'orphanRecordsService',
        \CReifenscheid\CleanupTools\Service\CleanupService\OrphanRecordsService::class
    );
    
    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addCleanupService(
        'missingFilesService',
        \CReifenscheid\CleanupTools\Service\CleanupService\MissingFilesService::class
    );
    
    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addCleanupService(
        'missingRelationsService',
        \CReifenscheid\CleanupTools\Service\CleanupService\MissingRelationsService::class
    );
    
    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addCleanupService(
        'filesWithMultipleReferencesService',
        \CReifenscheid\CleanupTools\Service\CleanupService\FilesWithMultipleReferencesService::class
    );

    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addCleanupService(
        'corruptMMRelationsService',
        \CReifenscheid\CleanupTools\Service\CleanupService\CorruptMMRelationsService::class
    );
    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addLocalizationFilePath('EXT:cleanup_tools/Resources/Private/Language/Services/locallang_corruptMMRelationsService.xlf');

    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addLocalizationFilePath('EXT:cleanup_tools/Resources/Private/Language/locallang_descriptions.xlf');
    \CReifenscheid\CleanupTools\Utility\ConfigurationManagementUtility::addLocalizationFilePath('EXT:cleanup_tools/Resources/Private/Language/locallang_parameters.xlf');
    
    // ICONS
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Imaging\IconRegistry::class);
    
    $icons = [
        'tx-cleanuptools-icon' => 'EXT:cleanup_tools/Resources/Public/Icons/tx_cleanuptools_icon.svg',
        'tx-cleanuptools-widget-icon' => 'EXT:cleanup_tools/Resources/Public/Icons/tx_cleanuptools_dashboard_widget.svg'
    ];
    
    foreach ($icons as $iconKey => $pathToIcon) {
        $iconRegistry->registerIcon($iconKey, TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class, [
            'source' => $pathToIcon
        ]);
    }
    
    // EXTENSION CONFIGURATION
    $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('cleanup_tools');
    
    // TOOLBAR ITEM
    if ($extensionConfiguration['enableToolbarItem']) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][1435433112] = \CReifenscheid\CleanupTools\Backend\Toolbar\CleanupToolbarItem::class;
    }
    
    // HOOK: After database operations hook
    if ($extensionConfiguration['enableAfterDatabaseOperationsHook']) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['cleanup_tools'] = \CReifenscheid\CleanupTools\Hooks\AfterDatabaseOperationsHook::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['cleanup_tools'] = \CReifenscheid\CleanupTools\Hooks\AfterDatabaseOperationsHook::class;
    }
    
    // HOOK: DrawItem
    if ($extensionConfiguration['enablePreviewRenderer']) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'][] = \CReifenscheid\CleanupTools\Hooks\PreviewRenderer::class;
    }
    
    // TASK
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\CReifenscheid\CleanupTools\Task\CleanupTask::class] = [
        'extension' => 'cleanup_tools',
        'title' => 'LLL:EXT:cleanup_tools/Resources/Private/Language/locallang_mod.xlf:tasks.cleanup.title',
        'description' => 'LLL:EXT:cleanup_tools/Resources/Private/Language/locallang_mod.xlf:tasks.cleanup.description',
        'additionalFields' => \CReifenscheid\CleanupTools\Task\CleanupAdditionalFieldProvider::class
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\CReifenscheid\CleanupTools\Task\HistoryTask::class] = [
        'extension' => 'cleanup_tools',
        'title' => 'LLL:EXT:cleanup_tools/Resources/Private/Language/locallang_mod.xlf:tasks.history.title',
        'description' => 'LLL:EXT:cleanup_tools/Resources/Private/Language/locallang_mod.xlf:tasks.history.description',
        'additionalFields' => \CReifenscheid\CleanupTools\Task\HistoryAdditionalFieldProvider::class
    ];
    
    // DASHBOARD
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
        module.tx_dashboard {
            view {
                templateRootPaths.21121984 = EXT:cleanup_tools/Resources/Private/Templates/
                partialRootPaths.21121984 = EXT:cleanup_tools/Resources/Private/Partials/
                layoutRootPaths.21121984 = EXT:cleanup_tools/Resources/Private/Layouts/
            }
        }
    ');
    
    // VIEWHELPER NAMESPACE
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['ct'] = ['CReifenscheid\\CleanupTools\\ViewHelpers'];
    
})('cleanup_tools');