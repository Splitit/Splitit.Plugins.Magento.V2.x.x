<?php

namespace Splitit\PaymentGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use SplititSdkClient\Model\TouchPoint;

class TouchpointHelper extends AbstractHelper
{
    const MODULE_NAME = 'Splitit_PaymentGateway';
    const PLUGIN_CODE = 'MagentoPlugin';

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Context $context,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList
    ) {
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        parent::__construct($context);
    }

    /**
     * Gets Touch Point Data
     *
     * @return SplititSdkClient\Model\TouchPoint
     */
    public function getTouchPointData()
    {
        $magentoVersion = $this->productMetadata->getVersion();
        $moduleVersion = $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
        $touchPointData = new TouchPoint();
        $touchPointData->setCode(self::PLUGIN_CODE);
        $touchPointData->setVersion($magentoVersion);
        $touchPointData->setSubVersion($moduleVersion);

        return $touchPointData;
    }
}
