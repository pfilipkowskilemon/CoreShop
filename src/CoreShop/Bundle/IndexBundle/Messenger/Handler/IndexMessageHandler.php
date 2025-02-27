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

namespace CoreShop\Bundle\IndexBundle\Messenger\Handler;

use CoreShop\Bundle\IndexBundle\Messenger\IndexMessage;
use CoreShop\Component\Index\Model\IndexableInterface;
use CoreShop\Component\Index\Service\IndexUpdaterServiceInterface;
use CoreShop\Component\Pimcore\DataObject\InheritanceHelper;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class IndexMessageHandler implements MessageHandlerInterface
{
    private array $validObjectTypes = [AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_VARIANT];

    public function __construct(
        private IndexUpdaterServiceInterface $indexUpdaterService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(IndexMessage $indexMessage)
    {
        $indexable = Concrete::getById($indexMessage->getIndexableId());

        if (!$indexable instanceof IndexableInterface) {
            throw new UnrecoverableMessageHandlingException('Indexable given does not implement IndexableInterface');
        }

        InheritanceHelper::useInheritedValues(function () use ($indexable, $indexMessage) {
            if ($indexMessage->isDelete()) {
                $this->indexUpdaterService->removeIndices($indexable);

                return;
            }

            $this->indexUpdaterService->updateIndices($indexable, $indexMessage->isSaveVersionOnly());
        });

        $classDefinition = ClassDefinition::getById($indexable->getClassId());

        if ($classDefinition && ($classDefinition->getAllowInherit() || $classDefinition->getAllowVariants())) {
            $this->updateInheritableChildren($indexable, $indexMessage->isSaveVersionOnly());
        }
    }

    private function updateInheritableChildren(AbstractObject $object, bool $isVersionChange): void
    {
        if (!$object->hasChildren($this->validObjectTypes)) {
            return;
        }

        $children = $object->getChildren($this->validObjectTypes);

        foreach ($children as $child) {
            if ($child instanceof IndexableInterface && $child::class === $object::class) {
                $this->messageBus->dispatch(new IndexMessage($child->getId(), $isVersionChange));
            }
        }
    }
}
