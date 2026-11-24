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
use PSBits\EpisodeGuide\Domain\Model\Episode;
use PSBits\EpisodeGuide\Domain\Model\Season;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class SeasonTest
 *
 * @package PSBits\EpisodeGuide\Tests\Unit\Domain\Model
 */
class SeasonTest extends UnitTestCase
{
    #[Test]
    public function episodesAreEmptyByDefault(): void
    {
        $season = new Season();

        self::assertCount(0, $season->getEpisodes());
    }

    #[Test]
    public function episodesCanBeAdded(): void
    {
        $season  = new Season();
        $episode = new Episode();

        $season->addEpisode($episode);

        self::assertCount(1, $season->getEpisodes());
        self::assertContains($episode, $season->getEpisodes());
    }

    #[Test]
    public function episodesCanBeRemoved(): void
    {
        $season   = new Season();
        $episodeA = new Episode();
        $episodeB = new Episode();

        $season->addEpisode($episodeA)
            ->addEpisode($episodeB);
        $season->removeEpisode($episodeA);

        self::assertCount(1, $season->getEpisodes());
        self::assertNotContains($episodeA, $season->getEpisodes());
        self::assertContains($episodeB, $season->getEpisodes());
    }
}
