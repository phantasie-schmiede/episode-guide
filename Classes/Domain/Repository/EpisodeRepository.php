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
 * Class EpisodeRepository
 *
 * @extends Repository<Episode>
 * @package PSBits\EpisodeGuide\Domain\Repository
 */
class EpisodeRepository extends Repository
{
    /**
     * @return array<Episode>
     */
    public function findBySeriesOrderedBySeasonAndEpisodeNumber(Series $series): array
    {
        $query = $this->createQuery();
        $query->matching($query->equals('season.series', $series));
        $query->setOrderings([
            'season.seasonNumber' => QueryInterface::ORDER_ASCENDING,
            'episodeNumber'       => QueryInterface::ORDER_ASCENDING,
            'uid'                 => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute()
            ->toArray();
    }
}
