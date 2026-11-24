<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Controller;

use InvalidArgumentException;
use JsonException;
use PSBits\EpisodeGuide\Domain\Model\Episode;
use PSBits\EpisodeGuide\Domain\Model\Season;
use PSBits\EpisodeGuide\Domain\Model\Series;
use PSBits\EpisodeGuide\Domain\Repository\EpisodeRepository;
use PSBits\EpisodeGuide\Domain\Repository\SeriesRepository;
use PSBits\Foundation\Attribute\PluginAction;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use ValueError;

use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function function_exists;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;
use function max;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;
use function usort;

class SeriesController extends ActionController
{
    public function __construct(
        protected readonly EpisodeRepository $episodeRepository,
        protected readonly SeriesRepository  $seriesRepository,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidArgumentException
     */
    #[PluginAction(uncached: true)]
    public function createAction(Series $series): ResponseInterface
    {
        $this->seriesRepository->add($series);
        $this->addFlashMessage('Series created successfully.');

        return $this->redirect('list');
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidArgumentException
     */
    #[PluginAction(uncached: true)]
    public function deleteAction(Series $series): ResponseInterface
    {
        $this->seriesRepository->remove($series);
        $this->addFlashMessage('Series deleted successfully.');

        return $this->redirect('list');
    }

    #[PluginAction(uncached: true)]
    public function editAction(Series $series): ResponseInterface
    {
        $this->view->assign('series', $series);

        return $this->htmlResponse();
    }

    #[PluginAction]
    public function listAction(): ResponseInterface
    {
        $this->view->assign('series', $this->seriesRepository->findAll());

        return $this->htmlResponse();
    }

    #[IgnoreValidation(['value' => 'newSeries'])]
    #[PluginAction(uncached: true)]
    public function newAction(?Series $newSeries = null): ResponseInterface
    {
        $this->view->assign('series', $newSeries);

        return $this->htmlResponse();
    }

    /**
     * @throws JsonException
     * @throws ValueError
     */
    #[PluginAction(uncached: true)]
    public function searchAction(Series $series, int $page = 1, string $query = ''): ResponseInterface
    {
        $query          = trim($query);
        $results        = [];
        $hasSearched    = '' !== $query;
        $resultsPerPage = 20;

        if ($hasSearched) {
            $terms = $this->parseSearchTerms($query);

            if ([] !== $terms['positive'] || [] !== $terms['negative']) {
                $episodes = $this->episodeRepository->findBySeriesOrderedBySeasonAndEpisodeNumber($series);

                foreach ($episodes as $episode) {
                    $result = $this->buildEpisodeSearchResult($episode, $terms['positive'], $terms['negative']);

                    if (null !== $result) {
                        $results[] = $result;
                    }
                }
            }
        }

        $pagination   = $this->buildPagination(count($results), $page, $resultsPerPage);
        $pagedResults = array_slice($results, $pagination['offset'], $resultsPerPage);

        $this->view->assignMultiple([
            'hasSearched'  => $hasSearched,
            'pagination'   => $pagination,
            'page'         => $pagination['currentPage'],
            'query'        => $query,
            'results'      => $pagedResults,
            'series'       => $series,
            'totalResults' => count($results),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Default action of the plugin.
     *
     * If no series is passed via request, the series selected in the plugin's FlexForm is used. Without any usable
     * series the action falls back to the list view.
     *
     * @throws InvalidArgumentException
     */
    #[IgnoreValidation(['value' => 'series'])]
    #[PluginAction(default: true)]
    public function showAction(?Series $series = null): ResponseInterface
    {
        $series ??= $this->getConfiguredSeries();

        if (!$series instanceof Series) {
            return new ForwardResponse('list');
        }

        $this->view->assign('series', $series);

        return $this->htmlResponse();
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidArgumentException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function updateAction(Series $series): ResponseInterface
    {
        $this->seriesRepository->update($series);
        $this->addFlashMessage('Series updated successfully.');

        return $this->redirect('list');
    }

    /**
     * @param array<string> $negativeTerms
     * @param array<string> $positiveTerms
     * @return array<string, mixed>|null
     * @throws JsonException
     * @throws ValueError
     */
    private function buildEpisodeSearchResult(Episode $episode, array $positiveTerms, array $negativeTerms): ?array
    {
        $title             = $episode->getTitle();
        $summary           = $episode->getSummary();
        $transcriptEntries = $this->extractTranscriptEntries($episode->getTranscript());

        $haystacks = [
            $title,
            $summary,
            $episode->getTranscript(),
        ];

        /*
         * Temporary solution for the current transcript format. First element is the whole text, which is duplicated by
         * the following diarized parts.
         * This removes the large block of the complete transcript in the preview without affecting the result of the
         * search.
         */
        array_shift($transcriptEntries);

        foreach ($transcriptEntries as $entry) {
            $haystacks[] = $entry['text'];
        }

        if ($this->containsAnyTermsInAnyHaystack($haystacks, $negativeTerms)) {
            return null;
        }

        if (!$this->containsAllTermsInAnyHaystack($haystacks, $positiveTerms)) {
            return null;
        }

        $matches = [];

        if ($this->containsAnyTerms($title, $positiveTerms)) {
            $matches[] = [
                'content'  => $this->highlightText($title, $positiveTerms),
                'path'     => '',
                'property' => 'title',
            ];
        }

        if ('' !== trim($summary) && $this->containsAnyTerms($summary, $positiveTerms)) {
            $matches[] = [
                'content'  => $this->highlightText($summary, $positiveTerms),
                'path'     => '',
                'property' => 'summary',
            ];
        }

        foreach ($transcriptEntries as $entry) {
            if ($this->containsAnyTerms($entry['text'], $positiveTerms)) {
                $matches[] = [
                    'content'  => $this->highlightText($entry['text'], $positiveTerms),
                    'path'     => $entry['path'],
                    'property' => 'transcript',
                ];
            }
        }

        if ([] === $matches) {
            $matches[] = [
                'content'  => $this->highlightText($title, $positiveTerms),
                'path'     => '',
                'property' => 'title',
            ];
        }

        $season       = $episode->getSeason();
        $seasonNumber = null;
        $seasonTitle  = '';

        if ($season instanceof Season) {
            $seasonNumber = $season->getSeasonNumber();
            $seasonTitle  = $season->getTitle();
        }

        return [
            'episode'      => $episode,
            'matches'      => $matches,
            'seasonNumber' => $seasonNumber,
            'seasonTitle'  => $seasonTitle,
        ];
    }

    /**
     * @return array{
     *     currentPage: int,
     *     from: int,
     *     hasNextPage: bool,
     *     hasPreviousPage: bool,
     *     nextPage: int,
     *     offset: int,
     *     previousPage: int,
     *     to: int,
     *     total: int,
     *     totalPages: int
     * }
     */
    private function buildPagination(int $totalResults, int $page, int $resultsPerPage): array
    {
        $safePerPage = max(1, $resultsPerPage);
        $totalPages  = max(1, (int)ceil($totalResults / $safePerPage));
        $currentPage = min(max(1, $page), $totalPages);
        $offset      = ($currentPage - 1) * $safePerPage;

        $from = 0;
        $to   = 0;

        if (0 < $totalResults) {
            $from = $offset + 1;
            $to   = min($offset + $safePerPage, $totalResults);
        }

        return [
            'currentPage'     => $currentPage,
            'from'            => $from,
            'hasNextPage'     => $currentPage < $totalPages,
            'hasPreviousPage' => 1 < $currentPage,
            'nextPage'        => min($totalPages, $currentPage + 1),
            'offset'          => $offset,
            'previousPage'    => max(1, $currentPage - 1),
            'to'              => $to,
            'total'           => $totalResults,
            'totalPages'      => $totalPages,
        ];
    }

    /**
     * @param array<string> $haystacks
     * @param array<string> $terms
     * @throws ValueError
     */
    private function containsAllTermsInAnyHaystack(array $haystacks, array $terms): bool
    {
        foreach ($terms as $term) {
            $isMatched = false;

            foreach ($haystacks as $haystack) {
                if ($this->containsTerm($haystack, $term)) {
                    $isMatched = true;

                    break;
                }
            }

            if (!$isMatched) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string> $terms
     * @throws ValueError
     */
    private function containsAnyTerms(string $haystack, array $terms): bool
    {
        foreach ($terms as $term) {
            if ($this->containsTerm($haystack, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string> $haystacks
     * @param array<string> $terms
     * @throws ValueError
     */
    private function containsAnyTermsInAnyHaystack(array $haystacks, array $terms): bool
    {
        foreach ($haystacks as $haystack) {
            if ($this->containsAnyTerms($haystack, $terms)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ValueError
     */
    private function containsTerm(string $haystack, string $term): bool
    {
        if (function_exists('mb_stripos')) {
            return false !== mb_stripos($haystack, $term);
        }

        return false !== stripos($haystack, $term);
    }

    /**
     * @return array<array{path: string, text: string}>
     * @throws JsonException
     */
    private function extractTranscriptEntries(string $transcript): array
    {
        if ('' === trim($transcript)) {
            return [];
        }

        $decoded = json_decode($transcript, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            return [];
        }

        return $this->flattenJsonValue($decoded, '$');
    }

    /**
     * @return array<array{path: string, text: string}>
     */
    private function flattenJsonValue(mixed $value, string $path): array
    {
        if (is_array($value)) {
            $entries = [];
            $isList  = array_is_list($value);

            foreach ($value as $key => $childValue) {
                $childPath = $isList ? sprintf('%s[%d]', $path, (int)$key) : sprintf('%s.%s', $path, (string)$key);

                foreach ($this->flattenJsonValue($childValue, $childPath) as $flattenedEntry) {
                    $entries[] = $flattenedEntry;
                }
            }

            return $entries;
        }

        if (is_bool($value)) {
            return [
                [
                    'path' => $path,
                    'text' => $value ? 'true' : 'false',
                ],
            ];
        }

        if (is_float($value) || is_int($value) || is_string($value)) {
            $text = trim((string)$value);

            return '' === $text ? [] : [
                [
                    'path' => $path,
                    'text' => $text,
                ],
            ];
        }

        return [];
    }

    /**
     * Returns the series that has been selected in the plugin's FlexForm.
     */
    private function getConfiguredSeries(): ?Series
    {
        $seriesUid = (int)($this->settings['series'] ?? 0);

        if (0 >= $seriesUid) {
            return null;
        }

        return $this->seriesRepository->findByUid($seriesUid);
    }

    /**
     * @param array<string> $positiveTerms
     */
    private function highlightText(string $text, array $positiveTerms): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ([] === $positiveTerms) {
            return nl2br($escaped);
        }

        $terms = array_values(array_unique($positiveTerms));
        usort($terms, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($terms as $term) {
            $escapedTerm = htmlspecialchars($term, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            if ('' === $escapedTerm) {
                continue;
            }

            $pattern = '/' . preg_quote($escapedTerm, '/') . '/iu';
            $escaped = preg_replace($pattern, '<mark>$0</mark>', $escaped);
        }

        return nl2br($escaped);
    }

    /**
     * @return array{negative: array<string>, positive: array<string>}
     */
    private function parseSearchTerms(string $query): array
    {
        preg_match_all('/-?"[^"]+"|-?\S+/u', $query, $matches);

        $positive = [];
        $negative = [];

        foreach ($matches[0] as $token) {
            $isNegative = str_starts_with($token, '-');
            $rawTerm    = $isNegative ? substr($token, 1) : $token;
            $term       = trim($rawTerm, " \t\n\r\0\x0B\"");

            if ('' === $term) {
                continue;
            }

            if ($isNegative) {
                $negative[] = $term;

                continue;
            }

            $positive[] = $term;
        }

        return [
            'negative' => array_values(array_unique($negative)),
            'positive' => array_values(array_unique($positive)),
        ];
    }
}
