<?php

namespace Splitit\PaymentGateway\Model;

use Magento\Framework\Model\AbstractModel;
use Splitit\PaymentGateway\Model\ResourceModel\Log as LogResource;

class Log extends AbstractModel
{
    const ENTITY_ID = 'entity_id';
    const QUOTE_ID = 'quote_id';
    const INCREMENT_ID = 'increment_id';
    const SUCCESS = 'success';
    const ASYNC = 'async';
    const INSTALLMENT_PLAN_NUMBER = 'installment_plan_number';

    public function _construct()
    {
        $this->_init(LogResource::class);
    }

    /**
     * @return int
     */
    public function getQuoteId()
    {
        return (int) $this->_getData(self::QUOTE_ID);
    }

    /**
     * @return int|null
     */
    public function getIncrementId()
    {
        return $this->_getData(self::INCREMENT_ID);
    }

    /**
     * @return bool|null
     */
    public function isSuccess()
    {
        return $this->_getData(self::SUCCESS);
    }

    /**
     * @return bool|null
     */
    public function isAsync()
    {
        return $this->_getData(self::ASYNC);
    }

    /**
     * @return string
     */
    public function getInstallmentPlanNumber()
    {
        return (string) $this->_getData(self::INSTALLMENT_PLAN_NUMBER);
    }

    /**
     * @param int $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId)
    {
        $this->setData(self::QUOTE_ID, $quoteId);

        return $this;
    }

    /**
     * @param int|null $incrementId
     * @return $this
     */
    public function setIncrementId($incrementId = null)
    {
        $this->setData(self::INCREMENT_ID, $incrementId);

        return $this;
    }

    /**
     * @param bool|null $isSuccess
     * @return $this
     */
    public function setIsSuccess($isSuccess = null)
    {
        $this->setData(self::SUCCESS, $isSuccess);

        return $this;
    }

    /**
     * @param bool|null $isAsync
     * @return $this
     */
    public function setIsAsync($isAsync = null)
    {
        $this->setData(self::ASYNC, $isAsync);

        return $this;
    }

    /**
     * @param $installmentPlanNumber
     * @return $this
     */
    public function setInstallmentPlanNumber($installmentPlanNumber)
    {
        $this->setData(self::INSTALLMENT_PLAN_NUMBER, $installmentPlanNumber);

        return $this;
    }
}
