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
        $this->addColumn('installment', ['label' => __('No. Of  Installments (Comma separated)'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';

        if ($element->getTooltip()) {
            $html .= '<td class="value with-tooltip">';
            if ($element->getComment()) {
                $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
            }
            $html .= $this->_getElementHtml($element);
            $html .= '<div class="tooltip"><span class="help"><span></span></span>';
            $html .= '<div class="tooltip-content">' . $element->getTooltip() . '</div></div>';
        } else {
            $html .= '<td class="value">';
            if ($element->getComment()) {
                $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
            }
            $html .= $this->_getElementHtml($element);
        }

        $html .= '</td>';
        return $html;
    }
}
