<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Tests\Unit\Data;

use PHPUnit\Framework\TestCase;
use PSBits\EpisodeGuide\Controller\EpisodeController;
use PSBits\EpisodeGuide\Controller\SeasonController;
use PSBits\EpisodeGuide\Controller\SeriesController;
use PSBits\EpisodeGuide\Data\ExtensionInformation;
use PSBits\Foundation\Data\PluginConfiguration;

final class ExtensionInformationTest extends TestCase
{
    public function testRegistersTheSeriesPlugin(): void
    {
        $extensionInformation = new ExtensionInformation();

        self::assertSame([], $extensionInformation->getMainModules());
        self::assertSame([], $extensionInformation->getModules());
        self::assertSame([], $extensionInformation->getPageTypes());

        $plugins = $extensionInformation->getPlugins();

        self::assertCount(1, $plugins);
        self::assertContainsOnlyInstancesOf(PluginConfiguration::class, $plugins);

        $series = $plugins[0];
        self::assertSame('Series', $series->getName());
        self::assertSame(
            [
                SeriesController::class,
                SeasonController::class,
                EpisodeController::class,
            ],
            $series->getControllers()
        );
    }
}
