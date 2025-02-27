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

namespace CoreShop\Bundle\IndexBundle\Messenger;

class IndexMessage
{
    public function __construct(
        protected int $indexableId,
        protected bool $saveVersionOnly = false,
        protected bool $isDelete = false,
    ) {
    }

    public function getIndexableId(): int
    {
        return $this->indexableId;
    }

    public function isSaveVersionOnly(): bool
    {
        return $this->saveVersionOnly;
    }

    public function isDelete(): bool
    {
        return $this->isDelete;
    }
}
