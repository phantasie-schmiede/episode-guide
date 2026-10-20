<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Domain\Repository;

use PSBits\EpisodeGuide\Domain\Model\Series;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class SeriesRepository
 *
 * @extends Repository<Series>
 * @package PSBits\EpisodeGuide\Domain\Repository
 */
class SeriesRepository extends Repository
{
    /**
     * @return array<Series>
     */
    public function findAllOrderedByUid(): array
    {
        $query = $this->createQuery();
        $query->setOrderings([
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->execute()->toArray();
    }
}
