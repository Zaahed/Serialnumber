<?php

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$serialnumber = $objectManager->get(\Zaahed\Serialnumber\Api\Data\SerialnumberInterface::class);
$serialnumberRepository = $objectManager->get(\Zaahed\Serialnumber\Api\SerialnumberRepositoryInterface::class);

$serialnumber->setSerialnumber('A7B2D9F1G4');
$serialnumber->setIsAvailable(true);
$serialnumber->setPurchasePrice(99.99);
$serialnumberRepository->save($serialnumber);
