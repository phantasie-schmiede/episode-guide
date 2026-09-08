<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Domain\Repository;

use PSBits\EpisodeGuide\Domain\Model\Season;
use PSBits\EpisodeGuide\Domain\Model\Series;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class SeasonRepository
 *
 * @extends Repository<Season>
 * @package PSBits\EpisodeGuide\Domain\Repository
 */
class SeasonRepository extends Repository
{
    /**
     * @return array<Season>
     */
    public function findBySeriesOrderedBySeasonNumber(Series $series): array
    {
        $query = $this->createQuery();
        $query->matching($query->equals('series', $series));
        $query->setOrderings([
            'seasonNumber' => QueryInterface::ORDER_ASCENDING,
            'uid'          => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute()
            ->toArray();
    }
}
