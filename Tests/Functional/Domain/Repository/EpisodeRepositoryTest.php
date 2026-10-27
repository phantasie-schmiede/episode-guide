<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PSBits\EpisodeGuide\Domain\Model\Episode;
use PSBits\EpisodeGuide\Domain\Model\Season;
use PSBits\EpisodeGuide\Domain\Model\Series;
use PSBits\EpisodeGuide\Domain\Repository\EpisodeRepository;
use PSBits\EpisodeGuide\Domain\Repository\SeasonRepository;
use PSBits\EpisodeGuide\Domain\Repository\SeriesRepository;
use PSBits\EpisodeGuide\Tests\Functional\Utility\TemplatesBootState;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class EpisodeRepositoryTest
 *
 * @package PSBits\EpisodeGuide\Tests\Functional\Domain\Repository
 */
class EpisodeRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid_styled_content',
        'typo3conf/ext/psbits/episode-guide',
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbits/templates',
        'typo3conf/ext/psbits/view-helpers',
    ];

    protected function setUp(): void
    {
        TemplatesBootState::resetMainTemplatesSettingsSources();

        parent::setUp();
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    #[Test]
    public function episodesOfASeriesAreFoundOrderedBySeasonAndEpisodeNumber(): void
    {
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $seriesRepository   = GeneralUtility::makeInstance(SeriesRepository::class);
        $seasonRepository   = GeneralUtility::makeInstance(SeasonRepository::class);
        $episodeRepository  = GeneralUtility::makeInstance(EpisodeRepository::class);

        $seriesA = $this->createSeries('Alpha Series');
        $seriesB = $this->createSeries('Beta Series');
        $seriesRepository->add($seriesA);
        $seriesRepository->add($seriesB);
        $persistenceManager->persistAll();
        $persistenceManager->clearState();

        $seasonA = $this->createSeason($seriesA, 1, 'First Season');
        $seasonB = $this->createSeason($seriesA, 2, 'Second Season');
        $seasonC = $this->createSeason($seriesB, 1, 'Foreign Season');
        $seasonRepository->add($seasonA);
        $seasonRepository->add($seasonB);
        $seasonRepository->add($seasonC);
        $persistenceManager->persistAll();
        $persistenceManager->clearState();

        // Deliberately insert in scrambled order.
        $episodeA2 = $this->createEpisode($seasonA, 2, 'Alpha Episode Two');
        $episodeA1 = $this->createEpisode($seasonA, 1, 'Alpha Episode One');
        $episodeB1 = $this->createEpisode($seasonB, 1, 'Beta Episode One');
        $episodeC1 = $this->createEpisode($seasonC, 1, 'Foreign Episode One');
        $episodeRepository->add($episodeA2);
        $episodeRepository->add($episodeA1);
        $episodeRepository->add($episodeB1);
        $episodeRepository->add($episodeC1);
        $persistenceManager->persistAll();
        $persistenceManager->clearState();

        $episodes = $episodeRepository->findBySeriesOrderedBySeasonAndEpisodeNumber($seriesA);

        self::assertCount(3, $episodes);
        self::assertSame('Alpha Episode One', $episodes[0]->getTitle());
        self::assertSame(1, $episodes[0]->getEpisodeNumber());
        self::assertSame($seasonA->getUid(), $episodes[0]->getSeason()->getUid());
        self::assertSame('Alpha Episode Two', $episodes[1]->getTitle());
        self::assertSame(2, $episodes[1]->getEpisodeNumber());
        self::assertSame($seasonA->getUid(), $episodes[1]->getSeason()->getUid());
        self::assertSame('Beta Episode One', $episodes[2]->getTitle());
        self::assertSame(1, $episodes[2]->getEpisodeNumber());
        self::assertSame($seasonB->getUid(), $episodes[2]->getSeason()->getUid());
    }

    private function createEpisode(Season $season, int $episodeNumber, string $title): Episode
    {
        $episode = new Episode();
        $episode->setSeason($season);
        $episode->setEpisodeNumber($episodeNumber);
        $episode->setTitle($title);
        $episode->setPid(1);

        return $episode;
    }

    private function createSeason(Series $series, int $seasonNumber, string $title): Season
    {
        $season = new Season();
        $season->setSeries($series);
        $season->setSeasonNumber($seasonNumber);
        $season->setTitle($title);
        $season->setPid(1);

        return $season;
    }

    private function createSeries(string $title): Series
    {
        $series = new Series();
        $series->setTitle($title);
        $series->setPid(1);

        return $series;
    }
}
