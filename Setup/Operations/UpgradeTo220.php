<?php

namespace Splitit\PaymentGateway\Setup\Operations;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use Splitit\PaymentGateway\Model\ResourceModel\Log as LogResource;
use Splitit\PaymentGateway\Model\Log as LogModel;

class UpgradeTo220
{
    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $this->createSplititLogTable($setup);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createSplititLogTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable(LogResource::TABLE_NAME)
        )->addColumn(
            LogModel::ENTITY_ID,
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            LogModel::QUOTE_ID,
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Magento Quote id'
        )->addColumn(
            LogModel::INCREMENT_ID,
            Table::TYPE_TEXT,
            32,
            ['nullable' => true, 'default' => null],
            'Magento Order Increment id'
        )->addColumn(
            LogModel::SUCCESS,
            Table::TYPE_BOOLEAN,
            null,
            ['nullable' => true, 'default' => null],
            'Is Success'
        )->addColumn(
            LogModel::ASYNC,
            Table::TYPE_BOOLEAN,
            null,
            ['nullable' => true, 'default' => null],
            'Is Async'
        )->addColumn(
            LogModel::INSTALLMENT_PLAN_NUMBER,
            Table::TYPE_TEXT,
            32,
            ['nullable' => false]
        )->addForeignKey(
            $setup->getFkName(
                LogResource::TABLE_NAME,
                LogModel::QUOTE_ID,
                $setup->getTable('quote'),
                'entity_id'
            ),
            LogModel::QUOTE_ID,
            $setup->getTable('quote'),
            'entity_id',
            Table::ACTION_CASCADE
        )->setComment(
            'Splitit PaymentGateway Log'
        );

        $setup->getConnection()->createTable($table);
    }
}
