<?php

namespace Splitit\PaymentGateway\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Splitit\PaymentGateway\Model\Log as LogEntity;
use Splitit\PaymentGateway\Model\LogFactory;

class Log extends AbstractDb
{
    const TABLE_NAME = 'splitit_paymentgateway_log';

    /**
     * @var LogFactory
     */
    private $logFactory;

    public function __construct(Context $context, LogFactory $logFactory, $connectionName = null)
    {
        $this->logFactory = $logFactory;
        parent::__construct($context, $connectionName);
    }

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, LogEntity::ENTITY_ID);
    }

    /**
     * @param $quoteId
     * @return bool|LogEntity
     */
    public function getByQuote($quoteId)
    {
        $log = $this->logFactory->create();
        $this->load($log, $quoteId, LogEntity::QUOTE_ID);
        if (!$log->getId()) {
            return false;
        }

        return $log;
    }

    /**
     * @param $ipn
     * @return bool|LogEntity
     */
    public function getByIPN($ipn)
    {
        $log = $this->logFactory->create();
        $this->load($log, $ipn, LogEntity::INSTALLMENT_PLAN_NUMBER);
        if (!$log->getId()) {
            return false;
        }

        return $log;
    }
}
