<?php

namespace Splitit\PaymentGateway\Setup;

use Splitit\PaymentGateway\Setup\Operations\UpgradeTo220;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var UpgradeTo220
     */
    private $upgradeTo220;

    public function __construct(UpgradeTo220 $upgradeTo220)
    {
        $this->upgradeTo220 = $upgradeTo220;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.2.0', '<')) {
            $this->upgradeTo220->execute($setup);
        }

        $setup->endSetup();
    }
}
