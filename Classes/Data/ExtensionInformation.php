<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Data;

use PSBits\EpisodeGuide\Controller\EpisodeController;
use PSBits\EpisodeGuide\Controller\SeasonController;
use PSBits\EpisodeGuide\Controller\SeriesController;
use PSBits\Foundation\Data\AbstractExtensionInformation;
use PSBits\Foundation\Data\PluginConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExtensionInformation
 *
 * @package PSBits\EpisodeGuide\Data
 */
class ExtensionInformation extends AbstractExtensionInformation
{
    public function __construct()
    {
        parent::__construct();
        /*
         * The order of the controllers matters: the first one is used as default controller by Extbase. Together with
         * SeriesController::showAction() being marked as #[PluginAction(default: true)] this makes the show view of the
         * series selected in the FlexForm (Configuration/FlexForms/Series.xml) the default action of the plugin.
         */
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [
                    SeriesController::class,
                    SeasonController::class,
                    EpisodeController::class,
                ],
                name       : 'Series',
            )
        );
    }
}
