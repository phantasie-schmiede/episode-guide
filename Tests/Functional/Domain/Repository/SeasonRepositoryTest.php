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
use PSBits\EpisodeGuide\Domain\Model\Season;
use PSBits\EpisodeGuide\Domain\Model\Series;
use PSBits\EpisodeGuide\Domain\Repository\SeasonRepository;
use PSBits\EpisodeGuide\Domain\Repository\SeriesRepository;
use PSBits\Qa\Testing\Support\PersistenceSupportTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class SeasonRepositoryTest
 *
 * @package PSBits\EpisodeGuide\Tests\Functional\Domain\Repository
 */
class SeasonRepositoryTest extends FunctionalTestCase
{
    use PersistenceSupportTrait;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid_styled_content',
        'typo3conf/ext/psbits/episode-guide',
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbits/frontend-templates',
        'typo3conf/ext/psbits/view-helpers',
    ];

    /**
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    #[Test]
    public function seasonsOfASeriesAreFoundInAscendingSeasonNumberOrder(): void
    {
        $seriesRepository = GeneralUtility::makeInstance(SeriesRepository::class);

        $seriesA = $this->createSeries('Alpha Series');
        $seriesB = $this->createSeries('Beta Series');
        $seriesRepository->add($seriesA);
        $seriesRepository->add($seriesB);
        $this->persistAll();

        $seasonRepository = GeneralUtility::makeInstance(SeasonRepository::class);

        // Deliberately insert in scrambled season number order.
        $seasonRepository->add($this->createSeason($seriesA, 3, 'Third Season'));
        $seasonRepository->add($this->createSeason($seriesA, 1, 'First Season'));
        $seasonRepository->add($this->createSeason($seriesA, 2, 'Second Season'));
        $seasonRepository->add($this->createSeason($seriesB, 1, 'Foreign Season'));
        $this->persistAll();

        $seasons = $seasonRepository->findBySeriesOrderedBySeasonNumber($seriesA);

        self::assertCount(3, $seasons);
        self::assertSame(1, $seasons[0]->getSeasonNumber());
        self::assertSame('First Season', $seasons[0]->getTitle());
        self::assertSame(2, $seasons[1]->getSeasonNumber());
        self::assertSame('Second Season', $seasons[1]->getTitle());
        self::assertSame(3, $seasons[2]->getSeasonNumber());
        self::assertSame('Third Season', $seasons[2]->getTitle());

        foreach ($seasons as $season) {
            self::assertNotNull($season->getSeries());
            self::assertSame($seriesA->getUid(), $season->getSeries()->getUid());
        }
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
