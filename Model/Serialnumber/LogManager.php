<?php
declare(strict_types=1);

namespace Zaahed\Serialnumber\Model\Serialnumber;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session as BackendSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Log\SaveMultiple;
use Zaahed\Serialnumber\Model\Serialnumber;

class LogManager
{
    /**
     * @var Log[]
     */
    private $logItems = [];

    /**
     * @var LogFactory
     */
    private $logFactory;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var BackendSession
     */
    private $backendSession;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param LogFactory $logFactory
     * @param SaveMultiple $saveMultiple
     * @param BackendSession $backendSession
     * @param UserContextInterface $userContext
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        LogFactory $logFactory,
        SaveMultiple $saveMultiple,
        BackendSession $backendSession,
        UserContextInterface $userContext,
        CollectionFactory $collectionFactory
    ) {
        $this->logFactory = $logFactory;
        $this->saveMultiple = $saveMultiple;
        $this->backendSession = $backendSession;
        $this->userContext = $userContext;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Add new log entry.
     *
     * @param string $serialnumber
     * @param string $message
     * @param array $values
     * @return void
     */
    public function addLog(string $serialnumber, string $message, array $values = []): void
    {
        $serialnumber = strtoupper($serialnumber);
        $userId = $this->getUserId();

        $logEntry = $this->logFactory->create();
        $logEntry->setSerialnumber($serialnumber);
        $logEntry->setMessage($message, $values);
        $logEntry->setUserId((int)$userId);

        $this->logItems[] = $logEntry;
    }

    /**
     * Add new log entry by serial number ID.
     *
     * @param int $id
     * @param string $message
     * @param array $values
     * @return void
     */
    public function addLogBySerialnumberId(int $id, string $message, array $values = []): void
    {
        $userId = $this->getUserId();

        $logEntry = $this->logFactory->create();
        $logEntry->setSerialnumberId($id);
        $logEntry->setMessage($message, $values);
        $logEntry->setUserId((int)$userId);

        $this->logItems[] = $logEntry;
    }

    /**
     * Save log entries to DB.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function save(): void
    {
        $serialnumbers = [];

        foreach ($this->logItems as $logEntry) {
            if ($logEntry->getSerialnumberId() === null &&
                !in_array($logEntry->getSerialnumber(), $serialnumbers)) {
                $serialnumbers[] = $logEntry->getSerialnumber();
            }
        }
        $serialnumberIds = $this->getSerialnumberIds($serialnumbers);

        foreach ($this->logItems as $logEntry) {
            $serialnumber = $logEntry->getSerialnumber();

            if ($logEntry->getSerialnumberId() !== null) {
                continue;
            }
            if (!isset($serialnumberIds[$serialnumber])) {
                throw new NoSuchEntityException(
                    __('No serial number ID was found for serial number: %1', $serialnumber)
                );
            }

            $serialnumberId = (int)$serialnumberIds[$serialnumber];
            $logEntry->setSerialnumberId($serialnumberId);
        }

        $this->saveMultiple->execute($this->logItems);
        $this->logItems = [];
    }

    /**
     * Get serial number IDs with serial numbers as keys and IDs as values.
     *
     * @param array $serialnumbers
     * @return array
     */
    private function getSerialnumberIds($serialnumbers)
    {
        $result = [];

        if (empty($serialnumbers)) {
            return [];
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('serialnumber', ['in' => $serialnumbers]);

        /** @var Serialnumber $item */
        foreach ($collection as $item) {
            $result[$item->getSerialnumber()] = $item->getId();
        }

        return $result;
    }

    /**
     * Get user ID.
     *
     * @return int|null
     */
    private function getUserId()
    {
        if ($this->backendSession->getUser() !== null) {
            return $this->backendSession->getUser()->getId();
        }

        return $this->userContext->getUserId();
    }
}