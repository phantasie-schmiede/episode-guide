<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use PSBits\EpisodeGuide\Command\ImportEpisodesCommand;
use PSBits\EpisodeGuide\Domain\Model\Season;
use PSBits\EpisodeGuide\Domain\Model\Series;
use PSBits\EpisodeGuide\Domain\Repository\SeasonRepository;
use PSBits\EpisodeGuide\Domain\Repository\SeriesRepository;
use PSBits\EpisodeGuide\Tests\Functional\Utility\TemplatesBootState;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

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
 * @package PSBits\EpisodeGuide\Tests\Functional\Command
 */
class ImportEpisodesCommandTest extends FunctionalTestCase
{
    private const string EPISODE_TABLE = 'tx_episodeguide_domain_model_episode';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid_styled_content',
        'typo3conf/ext/psbits/episode-guide',
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbits/templates',
        'typo3conf/ext/psbits/view-helpers',
    ];

    private ?string $importDirectory = null;

    protected function setUp(): void
    {
        TemplatesBootState::resetMainTemplatesSettingsSources();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeImportDirectory();

        parent::tearDown();
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    #[Test]
    public function episodesAreImportedIntoTheSelectedSeason(): void
    {
        $seasonUid = $this->provideSeriesAndSeason();

        $directory = $this->createImportDirectory([
            '1 - Pilot.json'       => '{"id": 1}',
            '2 - Second Show.json' => '{"id": 2}',
        ]);

        $tester = new CommandTester(GeneralUtility::makeInstance(ImportEpisodesCommand::class));
        $tester->setInputs([
            $directory,
            '1',
            (string)$seasonUid,
        ]);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('2 episodes were imported.', $tester->getDisplay());

        $rows = $this->getConnectionPool()
            ->getConnectionForTable(self::EPISODE_TABLE)
            ->executeQuery(
                'SELECT uid, episode_number, title, transcript, season FROM ' . self::EPISODE_TABLE . ' ORDER BY episode_number'
            )
            ->fetchAllAssociative();

        self::assertCount(2, $rows);
        self::assertSame(1, (int)$rows[0]['episode_number']);
        self::assertSame('Pilot', $rows[0]['title']);
        self::assertSame('{"id": 1}', $rows[0]['transcript']);
        self::assertSame($seasonUid, (int)$rows[0]['season']);
        self::assertSame(2, (int)$rows[1]['episode_number']);
        self::assertSame('Second Show', $rows[1]['title']);
        self::assertSame('{"id": 2}', $rows[1]['transcript']);
        self::assertSame($seasonUid, (int)$rows[1]['season']);
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    #[Test]
    public function relativeImportPathsAreResolvedAgainstTheProjectPath(): void
    {
        $seasonUid = $this->provideSeriesAndSeason();

        $projectPath   = Environment::getProjectPath();
        $directoryName = 'functional-import-' . uniqid();
        $directory     = $projectPath . DIRECTORY_SEPARATOR . $directoryName;
        $this->importDirectory = $directory;
        mkdir($directory);
        file_put_contents($directory . DIRECTORY_SEPARATOR . '1 - Pilot.json', '{"id": 1}');

        $tester = new CommandTester(GeneralUtility::makeInstance(ImportEpisodesCommand::class));
        $tester->setInputs([
            $directoryName,
            '1',
            (string)$seasonUid,
        ]);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('1 episodes were imported.', $tester->getDisplay());

        $rows = $this->getConnectionPool()
            ->getConnectionForTable(self::EPISODE_TABLE)
            ->executeQuery('SELECT title FROM ' . self::EPISODE_TABLE)
            ->fetchAllAssociative();

        self::assertCount(1, $rows);
        self::assertSame('Pilot', $rows[0]['title']);
    }

    /**
     * Provides a single series with a single untitled season and returns the uid of the season.
     *
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    private function provideSeriesAndSeason(): int
    {
        $seriesRepository   = GeneralUtility::makeInstance(SeriesRepository::class);
        $seasonRepository   = GeneralUtility::makeInstance(SeasonRepository::class);
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $series = new Series();
        $series->setTitle('Test Series');
        $series->setPid(1);
        $seriesRepository->add($series);

        $season = new Season();
        $season->setSeries($series);
        $season->setSeasonNumber(1);
        $season->setPid(1);
        $seasonRepository->add($season);

        $persistenceManager->persistAll();
        $persistenceManager->clearState();

        self::assertNotNull($season->getUid());

        return $season->getUid();
    }

    /**
     * @param array<string, string> $files
     */
    private function createImportDirectory(array $files): string
    {
        $directory         = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'episode-guide-import-' . uniqid();
        $this->importDirectory = $directory;
        mkdir($directory);

        foreach ($files as $fileName => $content) {
            file_put_contents($directory . DIRECTORY_SEPARATOR . $fileName, $content);
        }

        return $directory;
    }

    private function removeImportDirectory(): void
    {
        if (null !== $this->importDirectory) {
            foreach (glob($this->importDirectory . DIRECTORY_SEPARATOR . '*.json') ?: [] as $jsonFile) {
                unlink($jsonFile);
            }

            rmdir($this->importDirectory);
            $this->importDirectory = null;
        }
    }
}
