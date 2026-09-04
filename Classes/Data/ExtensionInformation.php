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
        $this->addPlugin(
            GeneralUtility::makeInstance(
                PluginConfiguration::class,
                controllers: [
                    EpisodeController::class,
                    SeasonController::class,
                    SeriesController::class
                ],
                name       : 'AutocompleteList',
            )
        );
    }
}
