<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Tests\Functional\Utility;

use PSBits\Templates\Service\GlobalVariableProviders\SettingsProvider;
use ReflectionProperty;

use function class_exists;

/**
 * Class TemplatesBootState
 *
 * @package PSBits\EpisodeGuide\Tests\Functional\Utility
 */
final class TemplatesBootState
{
    /**
     * Resets the static source registry of the main branch psbits/templates extension before each
     * functional test instance boots. Its ext_localconf.php registers the HeaderButtonRepository
     * source without an idempotency check (fixed on the 0.x branch), so the second boot in the
     * same PHP process would throw "The source ... is already registered!".
     */
    public static function resetMainTemplatesSettingsSources(): void
    {
        if (!class_exists(SettingsProvider::class)) {
            return;
        }

        (new ReflectionProperty(SettingsProvider::class, 'sources'))
            ->setValue(null, []);
    }
}
