<?php

namespace Zaahed\Serialnumber\Ui\Component\Listing\Columns;

use Magento\User\Api\Data\UserInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

class UserId implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $userCollectionFactory;

    /**
     * @param CollectionFactory $userCollectionFactory
     */
    public function __construct(CollectionFactory $userCollectionFactory)
    {
        $this->userCollectionFactory = $userCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $result = [];
        $userCollection = $this->userCollectionFactory->create();

        /** @var UserInterface $user */
        foreach ($userCollection as $user) {
            $result[] = [
                'value' => $user->getId(),
                'label' => $user->getUserName()
            ];
        }

        return $result;
    }
}