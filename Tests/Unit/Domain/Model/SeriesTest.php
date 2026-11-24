<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use PSBits\EpisodeGuide\Domain\Model\Season;
use PSBits\EpisodeGuide\Domain\Model\Series;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class SeriesTest
 *
 * @package PSBits\EpisodeGuide\Tests\Unit\Domain\Model
 */
class SeriesTest extends UnitTestCase
{
    #[Test]
    public function seasonsAreEmptyByDefault(): void
    {
        $series = new Series();

        self::assertCount(0, $series->getSeasons());
    }

    #[Test]
    public function seasonsCanBeAdded(): void
    {
        $series  = new Series();
        $season  = new Season();

        $series->addSeason($season);

        self::assertCount(1, $series->getSeasons());
        self::assertContains($season, $series->getSeasons());
    }

    #[Test]
    public function seasonsCanBeRemoved(): void
    {
        $series   = new Series();
        $seasonA  = new Season();
        $seasonB  = new Season();

        $series->addSeason($seasonA)
            ->addSeason($seasonB);
        $series->removeSeason($seasonA);

        self::assertCount(1, $series->getSeasons());
        self::assertNotContains($seasonA, $series->getSeasons());
        self::assertContains($seasonB, $series->getSeasons());
    }
}
