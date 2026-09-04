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
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Series
 *
 * @package PSBits\EpisodeGuide\Domain\Model
 */
class Series extends AbstractEntity
{
    /** @var ObjectStorage<Season> */
    #[Inline(foreignField: 'series', linkedModel: Season::class)]
    protected ObjectStorage $seasons;

    #[Text]
    protected string $summary = '';

    #[Input]
    #[Validate(['validator' => 'NotEmpty'])]
    protected string $title = '';
}
