<?php

namespace Zaahed\Serialnumber\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Api\SerialnumberRepositoryInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber as Resource;

class SerialnumberRepository implements SerialnumberRepositoryInterface
{
    /**
     * @var SerialnumberFactory
     */
    private $serialnumberFactory;

    /**
     * @var Resource
     */
    private $resource;

    /**
     * @param SerialnumberFactory $serialnumberFactory
     * @param Resource $resource
     */
    public function __construct(
        SerialnumberFactory $serialnumberFactory,
        Resource $resource
    ) {
        $this->serialnumberFactory = $serialnumberFactory;
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function get(string $serialnumber): SerialnumberInterface
    {
        $serialnumberObject = $this->serialnumberFactory->create();
        $this->resource->load($serialnumberObject, $serialnumber, SerialnumberInterface::SERIALNUMBER);
        if ($serialnumberObject->getId() === null) {
            throw new NoSuchEntityException(
                __('Serial number %1 does not exist.', [$serialnumber])
            );
        }

        return $serialnumberObject;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $id): SerialnumberInterface
    {
        $serialnumber = $this->serialnumberFactory->create();
        $this->resource->load($serialnumber, $id);
        if ($serialnumber->getId() === null) {
            throw new NoSuchEntityException(
                __('Serial number with ID %1 does not exist.', [$id])
            );
        }

        return $serialnumber;
    }

    /**
     * Save serial number.
     *
     * @param SerialnumberInterface $serialnumber
     * @return void
     * @throws AlreadyExistsException
     */
    public function save(SerialnumberInterface $serialnumber)
    {
        $this->resource->save($serialnumber);
    }
}
