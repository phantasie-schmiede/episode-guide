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

    public function __construct()
    {
        $this->episodes = new ObjectStorage();
    }

    public function addEpisode(Episode $episode): self
    {
        $this->episodes->attach($episode);

        return $this;
    }

    /** @return ObjectStorage<Episode> */
    public function getEpisodes(): ObjectStorage
    {
        return $this->episodes;
    }

    public function getSeasonNumber(): int
    {
        return $this->seasonNumber;
    }

    public function getSeries(): ?Series
    {
        return $this->series;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function removeEpisode(Episode $episode): self
    {
        $this->episodes->detach($episode);

        return $this;
    }

    /** @param ObjectStorage<Episode> $episodes */
    public function setEpisodes(ObjectStorage $episodes): self
    {
        $this->episodes = $episodes;

        return $this;
    }

    public function setSeasonNumber(int $seasonNumber): self
    {
        $this->seasonNumber = $seasonNumber;

        return $this;
    }

    public function setSeries(?Series $series): self
    {
        $this->series = $series;

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
