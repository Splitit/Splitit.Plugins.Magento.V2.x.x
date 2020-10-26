<?php

namespace Splitit\PaymentGateway\Model\Adminhtml\Form\Field;
 
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Splitit\PaymentGateway\Model\Adminhtml\Form\Field\InstallmentOptions;
 
class Ranges extends AbstractFieldArray
{
    /**
     * Renders installment admin configuration
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('priceFrom', ['label' => __('Amount From'), 'class' => 'required-entry validate-number validate-zero-or-greater splitit-validate-to']);
        $this->addColumn('priceTo', ['label' => __('Amount To'), 'class' => 'required-entry validate-number validate-zero-or-greater splitit-validate-from']);
        $this->addColumn('installment', ['label' => __('No. Of  Installments'), 'class' => 'required-entry validate-number validate-zero-or-greater']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

}
