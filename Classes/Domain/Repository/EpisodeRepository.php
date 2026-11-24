<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Domain\Repository;

use PSBits\EpisodeGuide\Domain\Model\Episode;
use PSBits\EpisodeGuide\Domain\Model\Series;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Episode>
 */
class EpisodeRepository extends Repository
{
    /**
     * @return array<Episode>
     */
    public function findBySeriesOrderedBySeasonAndEpisodeNumber(Series $series): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('season.series', $series));
        $query->setOrderings([
            // PSBits/alphabetical_array_keys=off — Seasons must be ordered before episodes.
            'season.seasonNumber' => QueryInterface::ORDER_ASCENDING,
            'episodeNumber'       => QueryInterface::ORDER_ASCENDING,
            'uid'                 => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute()
            ->toArray();
    }
}
