<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Subscriber;

use Discord\Base\AppBundle\Model\ServerModule;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * MappingSubscriber Class
 */
class ORMMappingSubscriber implements EventSubscriber
{
    /**
     * @var string
     */
    private $serverClass;

    /**
     * ORMMappingSubscriber constructor.
     *
     * @param string $serverClass
     */
    public function __construct($serverClass)
    {
        $this->serverClass = $serverClass;
    }

    public function getSubscribedEvents()
    {
        return [Events::loadClassMetadata];
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->getName() === ServerModule::class) {
            $this->setServerModuleMetaData($metadata);
        }

        if ($metadata->getName() === $this->serverClass) {
            $this->setServerMetaData($metadata);
        }
    }

    private function setServerModuleMetaData(ClassMetadata $metadata)
    {
        $metadata->mapManyToOne(
            [
                'targetEntity' => $this->serverClass,
                'fieldName'    => 'server',
                'inversedBy'   => 'modules',
            ]
        );
    }

    private function setServerMetaData(ClassMetadata $metadata)
    {
        $metadata->mapOneToMany(
            [
                'targetEntity' => ServerModule::class,
                'fieldName'    => 'modules',
                'mappedBy'     => 'server',
            ]
        );
    }
}
