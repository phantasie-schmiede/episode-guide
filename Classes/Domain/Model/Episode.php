<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Domain\Model;

use DateTime;
use PSBits\Foundation\Attribute\TCA\ColumnType\Datetime as TcaDatetime;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Number;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class Episode
 *
 * @package PSBits\EpisodeGuide\Domain\Model
 */
class Episode extends AbstractEntity
{
    #[Number]
    protected int $episodeNumber = 0;

    #[TcaDatetime]
    protected ?DateTime $releaseDate = null;

    #[Select(linkedModel: Season::class)]
    protected ?Season $season = null;

    #[Text]
    protected string $summary = '';

    #[Input]
    #[Validate(['validator' => 'NotEmpty'])]
    protected string $title = '';

    #[Text]
    protected string $transcript = '';
}
