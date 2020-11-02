<?php

namespace Splitit\PaymentGateway\Model\ResourceModel\Log;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Splitit\PaymentGateway\Model\Log;
use Splitit\PaymentGateway\Model\ResourceModel\Log as LogResource;

class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init(Log::class, LogResource::class);
    }
}
