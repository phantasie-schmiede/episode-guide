<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Tests\Functional\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use PSBits\EpisodeGuide\Domain\Model\Episode;
use PSBits\EpisodeGuide\Domain\Model\Series;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ValidationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid_styled_content',
        'typo3conf/ext/psbits/episode-guide',
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbits/frontend-templates',
        'typo3conf/ext/psbits/view-helpers',
    ];

    #[Test]
    public function episodeAndSeriesTitlesRequireExactlyOneNotEmptyValidator(): void
    {
        foreach ([Episode::class, Series::class] as $modelClass) {
            $schema     = new ClassSchema($modelClass);
            $validators = $schema->getProperty('title')->getValidators();

            self::assertCount(1, $validators);
            self::assertSame('NotEmpty', $validators[0]['name']);
        }
    }
}
