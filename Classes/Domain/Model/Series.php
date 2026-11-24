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
use PSBits\Foundation\Attribute\TCA\Ctrl;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

#[Ctrl(
    defaultSortBy: 'title ASC',
    label        : 'title',
    searchFields : [
        'summary',
        'title',
    ],
)]
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

    public function __construct()
    {
        $this->seasons = new ObjectStorage();
    }

    public function addSeason(Season $season): self
    {
        $this->seasons->attach($season);

        return $this;
    }

    /** @return ObjectStorage<Season> */
    public function getSeasons(): ObjectStorage
    {
        return $this->seasons;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function removeSeason(Season $season): self
    {
        $this->seasons->detach($season);

        return $this;
    }

    /** @param ObjectStorage<Season> $seasons */
    public function setSeasons(ObjectStorage $seasons): self
    {
        $this->seasons = $seasons;

        return $this;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
