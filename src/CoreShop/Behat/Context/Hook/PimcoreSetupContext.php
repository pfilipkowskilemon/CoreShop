<?php

declare(strict_types=1);

/*
 * CoreShop
 *
 * This source file is available under two different licenses:
 *  - GNU General Public License version 3 (GPLv3)
 *  - CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.org)
 * @license    https://www.coreshop.org/license     GPLv3 and CCL
 *
 */

namespace CoreShop\Behat\Context\Hook;

use Behat\Behat\Context\Context;
use CoreShop\Behat\Service\Setup;

final class PimcoreSetupContext implements Context
{
    /**
     * @BeforeSuite
     */
    public static function setupPimcore(): void
    {
        if (getenv('CORESHOP_SKIP_DB_SETUP')) {
            return;
        }

        Setup::setupPimcore();
    }
}
