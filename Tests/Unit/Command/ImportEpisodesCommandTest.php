<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PSBits\EpisodeGuide\Command\ImportEpisodesCommand;
use PSBits\EpisodeGuide\Domain\Model\Episode;
use PSBits\EpisodeGuide\Domain\Model\Season;
use PSBits\EpisodeGuide\Domain\Model\Series;
use PSBits\EpisodeGuide\Domain\Repository\EpisodeRepository;
use PSBits\EpisodeGuide\Domain\Repository\SeasonRepository;
use PSBits\EpisodeGuide\Domain\Repository\SeriesRepository;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use function count;
use function file_put_contents;
use function glob;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;
use function uniqid;

/**
 * Class ImportEpisodesCommandTest
 *
 * @package PSBits\EpisodeGuide\Tests\Unit\Command
 */
class ImportEpisodesCommandTest extends UnitTestCase
{
    private EpisodeRepository&MockObject           $episodeRepository;
    private PersistenceManagerInterface&MockObject $persistenceManager;
    private SeasonRepository&MockObject            $seasonRepository;
    private SeriesRepository&MockObject            $seriesRepository;
    private ?string                                $importDirectory = null;

    /** @var list<Episode> */
    private array $addedEpisodes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->episodeRepository  = $this->createMock(EpisodeRepository::class);
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->seasonRepository   = $this->createMock(SeasonRepository::class);
        $this->seriesRepository   = $this->createMock(SeriesRepository::class);

        $this->episodeRepository->method('add')
            ->willReturnCallback(function (Episode $episode): void {
                $this->addedEpisodes[] = $episode;
            });
    }

    protected function tearDown(): void
    {
        if (null !== $this->importDirectory) {
            foreach (glob($this->importDirectory . DIRECTORY_SEPARATOR . '*.json') ?: [] as $jsonFile) {
                unlink($jsonFile);
            }

            rmdir($this->importDirectory);
            $this->importDirectory = null;
        }

        parent::tearDown();
    }

    #[Test]
    public function executionFailsWhenNoSeriesExist(): void
    {
        $this->seriesRepository->method('findAllOrderedByUid')
            ->willReturn([]);

        $tester = $this->executeCommand([
            $this->createImportDirectory(),
        ]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No series were found', $tester->getDisplay());
    }

    #[Test]
    public function executionFailsWhenImportDirectoryDoesNotExist(): void
    {
        $this->seriesRepository->method('findAllOrderedByUid')
            ->willReturn([
                $this->createSeries(1, 'Test Series'),
            ]);

        $tester = $this->executeCommand([
            '/nonexistent/import-directory',
        ]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('could not be found', $tester->getDisplay());
    }

    #[Test]
    public function executionFailsWhenImportDirectoryContainsNoJsonFiles(): void
    {
        $this->seriesRepository->method('findAllOrderedByUid')
            ->willReturn([
                $this->createSeries(1, 'Test Series'),
            ]);

        $tester = $this->executeCommand([
            $this->createImportDirectory(),
        ]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No JSON files were found', $tester->getDisplay());
    }

    #[Test]
    public function executionFailsWhenSelectedSeriesHasNoSeasons(): void
    {
        $series                       = $this->createSeries(1, 'Test Series');
        $this->seriesRepository->method('findAllOrderedByUid')
            ->willReturn([$series]);
        $this->seasonRepository->method('findBySeriesOrderedBySeasonNumber')
            ->with($series)
            ->willReturn([]);

        $tester = $this->executeCommand([
            $this->createImportDirectoryWithJsonFiles([
                '1 - Pilot.json' => '{"id": 1}',
            ]),
            '1',
        ]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('has no seasons', $tester->getDisplay());
    }

    #[Test]
    public function executionReasksUntilAValidSeriesUidIsEntered(): void
    {
        $series                       = $this->createSeries(1, 'Test Series');
        $this->seriesRepository->method('findAllOrderedByUid')
            ->willReturn([$series]);
        $this->seasonRepository->method('findBySeriesOrderedBySeasonNumber')
            ->willReturn([]);

        $tester = $this->executeCommand([
            $this->createImportDirectoryWithJsonFiles([
                '1 - Pilot.json' => '{"id": 1}',
            ]),
            '99',
            '1',
        ]);

        self::assertStringContainsString('The given UID does not belong to any series.', $tester->getDisplay());
        self::assertStringContainsString('has no seasons', $tester->getDisplay());
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    #[Test]
    public function executionImportsEpisodesFromAllJsonFiles(): void
    {
        $series = $this->createSeries(1, 'Test Series');
        $season = $this->createSeason(10, $series, 1, '');
        $this->seriesRepository->method('findAllOrderedByUid')
            ->willReturn([$series]);
        $this->seasonRepository->method('findBySeriesOrderedBySeasonNumber')
            ->willReturn([$season]);
        $this->persistenceManager->expects(self::once())
            ->method('persistAll');

        $directory = $this->createImportDirectoryWithJsonFiles([
            '1 - Pilot.json'       => '{"id": 1}',
            '2 - Second Show.json' => '{"id": 2}',
        ]);

        $tester = $this->executeCommand([
            $directory,
            '1',
            '10',
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('2 episodes were imported.', $tester->getDisplay());
        self::assertStringContainsString('Season 1', $tester->getDisplay());
        self::assertCount(2, $this->addedEpisodes);
        self::assertSame(1, $this->addedEpisodes[0]->getEpisodeNumber());
        self::assertSame('Pilot', $this->addedEpisodes[0]->getTitle());
        self::assertSame('{"id": 1}', $this->addedEpisodes[0]->getTranscript());
        self::assertSame($season, $this->addedEpisodes[0]->getSeason());
        self::assertSame(2, $this->addedEpisodes[1]->getEpisodeNumber());
        self::assertSame('Second Show', $this->addedEpisodes[1]->getTitle());
        self::assertSame('{"id": 2}', $this->addedEpisodes[1]->getTranscript());
    }

    #[Test]
    public function executionUsesTheFileNameForEpisodesWithoutNumberPrefix(): void
    {
        $series = $this->createSeries(1, 'Test Series');
        $season = $this->createSeason(10, $series, 1, 'Season 1');
        $this->seriesRepository->method('findAllOrderedByUid')
            ->willReturn([$series]);
        $this->seasonRepository->method('findBySeriesOrderedBySeasonNumber')
            ->willReturn([$season]);

        $directory = $this->createImportDirectoryWithJsonFiles([
            'Special Episode.json' => 'special content',
        ]);

        $tester = $this->executeCommand([
            $directory,
            '1',
            '10',
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 episodes were imported.', $tester->getDisplay());
        self::assertCount(1, $this->addedEpisodes);
        self::assertSame(0, $this->addedEpisodes[0]->getEpisodeNumber());
        self::assertSame('Special Episode.json', $this->addedEpisodes[0]->getTitle());
        self::assertSame('special content', $this->addedEpisodes[0]->getTranscript());
    }

    /**
     * @param list<string> $inputs
     */
    private function createImportDirectoryWithJsonFiles(array $files): string
    {
        $directory = $this->createImportDirectory();

        foreach ($files as $fileName => $content) {
            file_put_contents($directory . DIRECTORY_SEPARATOR . $fileName, $content);
        }

        return $directory;
    }

    private function createImportDirectory(): string
    {
        $directory         = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'episode-guide-import-' . uniqid();
        $this->importDirectory = $directory;
        mkdir($directory);

        return $directory;
    }

    /**
     * @param list<string> $inputs
     */
    private function executeCommand(array $inputs): CommandTester
    {
        $command = new ImportEpisodesCommand(
            $this->episodeRepository,
            $this->persistenceManager,
            $this->seasonRepository,
            $this->seriesRepository
        );
        $tester = new CommandTester($command);
        $tester->setInputs($inputs);

        $tester->execute([]);

        return $tester;
    }

    private function createSeason(int $uid, Series $series, int $seasonNumber, string $title): Season
    {
        $season = new Season();
        (new ReflectionProperty(AbstractDomainObject::class, 'uid'))
            ->setValue($season, $uid);
        $season->setSeries($series);
        $season->setSeasonNumber($seasonNumber);
        $season->setTitle($title);

        return $season;
    }

    private function createSeries(int $uid, string $title): Series
    {
        $series = new Series();
        (new ReflectionProperty(AbstractDomainObject::class, 'uid'))
            ->setValue($series, $uid);
        $series->setTitle($title);

        return $series;
    }
}
