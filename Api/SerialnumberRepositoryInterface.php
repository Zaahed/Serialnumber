<?php

namespace Zaahed\Serialnumber\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;

interface SerialnumberRepositoryInterface
{
    /**
     * Get serial number object by serial number.
     *
     * @param string $serialnumber
     * @return SerialnumberInterface
     * @throws NoSuchEntityException
     */
    public function get(string $serialnumber): SerialnumberInterface;

    /**
     * Get serial number by ID.
     *
     * @param int $id
     * @return SerialnumberInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): SerialnumberInterface;

    /**
     * Save serial number.
     *
     * @param SerialnumberInterface $serialnumber
     * @return void
     */
    public function save(SerialnumberInterface $serialnumber);
}