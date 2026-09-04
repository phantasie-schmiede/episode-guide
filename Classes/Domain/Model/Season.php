<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Domain\Model;

use PSBits\Foundation\Attribute\TCA\ColumnType\Inline;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Number;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Season
 *
 * @package PSBits\EpisodeGuide\Domain\Model
 */
class Season extends AbstractEntity
{
    /** @var ObjectStorage<Episode> */
    #[Inline(foreignField: 'season', linkedModel: Episode::class)]
    protected ObjectStorage $episodes;

    #[Number]
    protected int $seasonNumber = 0;

    #[Select(linkedModel: Series::class)]
    protected ?Series $series = null;

    #[Text]
    protected string $summary = '';

    #[Input]
    protected string $title = '';
}
