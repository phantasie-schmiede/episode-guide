<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Command;

use PSBits\EpisodeGuide\Domain\Model\Episode;
use PSBits\EpisodeGuide\Domain\Repository\EpisodeRepository;
use PSBits\EpisodeGuide\Domain\Repository\SeasonRepository;
use PSBits\EpisodeGuide\Domain\Repository\SeriesRepository;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

use function is_string;

/**
 * Class ImportEpisodesCommand
 *
 * @package PSBits\EpisodeGuide\Command
 */
#[AsCommand(name: 'episode-guide:import-episodes', description: 'Import episodes from JSON files into a selected season.')]
class ImportEpisodesCommand extends Command
{
    public function __construct(
        private readonly EpisodeRepository           $episodeRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly SeasonRepository            $seasonRepository,
        private readonly SeriesRepository            $seriesRepository,
    ) {
        parent::__construct();
    }

    /**
     * @throws IllegalObjectTypeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Episode Import');

        $seriesList = $this->seriesRepository->findAllOrderedByUid();

        if ([] === $seriesList) {
            $io->warning('No series were found. Please create a series first.');

            return Command::FAILURE;
        }

        $relativePath    = (string)$io->ask('Relative path to the import directory');
        $importDirectory = $this->resolveImportDirectory($relativePath);

        if (null === $importDirectory || !is_dir($importDirectory)) {
            $io->error('The provided directory could not be found.');

            return Command::FAILURE;
        }

        $jsonFiles = glob($importDirectory . DIRECTORY_SEPARATOR . '*.json') ?: [];
        sort($jsonFiles, SORT_NATURAL | SORT_FLAG_CASE);

        if ([] === $jsonFiles) {
            $io->warning('No JSON files were found in the provided directory.');

            return Command::FAILURE;
        }

        $seriesByUid = [];
        $seriesRows  = [];

        foreach ($seriesList as $series) {
            $seriesByUid[$series->getUid()] = $series;
            $seriesRows[]                   = [
                (string)$series->getUid(),
                $series->getTitle(),
            ];
        }

        $io->section('Available series');
        $io->table(
            [
                'UID',
                'Title',
            ],
            $seriesRows
        );

        $seriesUid = (int)$io->ask(
            'Series UID to import episodes for',
            null,
            static function(?string $value) use ($seriesByUid): int {
                if (!is_string($value) || '' === trim($value) || !ctype_digit($value)) {
                    throw new RuntimeException('Please enter a valid numeric UID.');
                }

                $uid = (int)$value;

                if (!isset($seriesByUid[$uid])) {
                    throw new RuntimeException('The given UID does not belong to any series.');
                }

                return $uid;
            }
        );

        $selectedSeries = $seriesByUid[$seriesUid];

        $seasonList = $this->seasonRepository->findBySeriesOrderedBySeasonNumber($selectedSeries);

        if ([] === $seasonList) {
            $io->warning('The selected series has no seasons. Please create a season first.');

            return Command::FAILURE;
        }

        $seasonByUid = [];
        $seasonRows  = [];

        foreach ($seasonList as $season) {
            $seasonByUid[$season->getUid()] = $season;
            $seasonNumber                   = $season->getSeasonNumber();
            $title                          = trim($season->getTitle());

            if ('' === $title) {
                $title = 'Season ' . $seasonNumber;
            }

            $seasonRows[] = [
                (string)$season->getUid(),
                $title,
                (string)$seasonNumber,
            ];
        }

        $io->section('Seasons for series: ' . $selectedSeries->getTitle());
        $io->table(
            [
                'UID',
                'Title',
                'Season number',
            ],
            $seasonRows
        );

        $seasonUid = (int)$io->ask(
            'Season UID to import episodes for',
            null,
            static function(?string $value) use ($seasonByUid): int {
                if (!is_string($value) || '' === trim($value) || !ctype_digit($value)) {
                    throw new RuntimeException('Please enter a valid numeric UID.');
                }

                $uid = (int)$value;

                if (!isset($seasonByUid[$uid])) {
                    throw new RuntimeException('The given UID does not belong to a season in this series.');
                }

                return $uid;
            }
        );

        $selectedSeason = $seasonByUid[$seasonUid];

        $importedEpisodes = [];

        foreach ($jsonFiles as $jsonFile) {
            $fileContent = file_get_contents($jsonFile);

            if (false === $fileContent) {
                $io->warning('File could not be read and was skipped: ' . basename($jsonFile));

                continue;
            }

            $fileName = basename($jsonFile);
            [
                $episodeNumber,
                $title,
            ] = $this->extractEpisodeMetadata($fileName);

            $episode = new Episode();
            $episode->setEpisodeNumber($episodeNumber);
            $episode->setSeason($selectedSeason);
            $episode->setTitle($title);
            $episode->setTranscript($fileContent);

            $this->episodeRepository->add($episode);

            $importedEpisodes[] = [
                'episode'       => $episode,
                'episodeNumber' => $episodeNumber,
                'sourceFile'    => $fileName,
                'title'         => $title,
            ];
        }

        if ([] === $importedEpisodes) {
            $io->warning('No episodes could be imported.');

            return Command::FAILURE;
        }

        $this->persistenceManager->persistAll();

        $resultRows = [];

        foreach ($importedEpisodes as $importedEpisode) {
            /** @var Episode $episode */
            $episode      = $importedEpisode['episode'];
            $resultRows[] = [
                (string)$episode->getUid(),
                (string)$importedEpisode['episodeNumber'],
                $importedEpisode['title'],
                $importedEpisode['sourceFile'],
            ];
        }

        $io->section('Imported episodes');
        $io->table(
            [
                'UID',
                'Number',
                'Title',
                'File',
            ],
            $resultRows
        );
        $io->success(sprintf('%d episodes were imported.', count($importedEpisodes)));

        return Command::SUCCESS;
    }

    /** @return array{0: int, 1: string} */
    private function extractEpisodeMetadata(string $fileName): array
    {
        if (1 === preg_match('/^(\d+)\s*-\s*(.+)\.json$/i', $fileName, $matches)) {
            return [
                (int)$matches[1],
                trim($matches[2]),
            ];
        }

        return [
            0,
            $fileName,
        ];
    }

    private function resolveImportDirectory(string $relativePath): ?string
    {
        $trimmedPath = trim($relativePath);

        if ('' === $trimmedPath) {
            return null;
        }

        if (str_starts_with($trimmedPath, DIRECTORY_SEPARATOR)) {
            return realpath($trimmedPath) ?: null;
        }

        $absolutePath = Environment::getProjectPath() . DIRECTORY_SEPARATOR . $trimmedPath;

        return realpath($absolutePath) ?: null;
    }
}
