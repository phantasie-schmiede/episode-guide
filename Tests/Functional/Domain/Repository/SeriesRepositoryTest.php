<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PSBits\EpisodeGuide\Domain\Repository\SeriesRepository;
use PSBits\EpisodeGuide\Tests\Functional\Utility\TemplatesBootState;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class SeriesRepositoryTest
 *
 * @package PSBits\EpisodeGuide\Tests\Functional\Domain\Repository
 */
class SeriesRepositoryTest extends FunctionalTestCase
{
    private const string TABLE_NAME = 'tx_episodeguide_domain_model_series';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid_styled_content',
        'typo3conf/ext/psbits/episode-guide',
        'typo3conf/ext/psbits/foundation',
        'typo3conf/ext/psbits/templates',
        'typo3conf/ext/psbits/view-helpers',
    ];

    protected function setUp(): void
    {
        TemplatesBootState::resetMainTemplatesSettingsSources();

        parent::setUp();
    }

    #[Test]
    public function seriesAreFoundInAscendingUidOrder(): void
    {
        $connection = $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_NAME);
        $connection->insert(self::TABLE_NAME, [
            'uid'     => 30,
            'pid'     => 1,
            'title'   => 'Gamma Series',
            'summary' => 'Gamma summary',
        ]);
        $connection->insert(self::TABLE_NAME, [
            'uid'     => 10,
            'pid'     => 1,
            'title'   => 'Alpha Series',
            'summary' => 'Alpha summary',
        ]);
        $connection->insert(self::TABLE_NAME, [
            'uid'     => 20,
            'pid'     => 2,
            'title'   => 'Beta Series',
            'summary' => 'Beta summary',
        ]);

        $series = GeneralUtility::makeInstance(SeriesRepository::class)
            ->findAllOrderedByUid();

        self::assertCount(3, $series);
        self::assertSame(10, $series[0]->getUid());
        self::assertSame('Alpha Series', $series[0]->getTitle());
        self::assertSame(20, $series[1]->getUid());
        self::assertSame('Beta Series', $series[1]->getTitle());
        self::assertSame(30, $series[2]->getUid());
        self::assertSame('Gamma Series', $series[2]->getTitle());
    }
}
