<?php
namespace Splitit\PaymentGateway\Model\Adminhtml\Form\Field;

class Checkbox extends \Magento\Framework\Data\Form\Element\Checkbox
{

    /**
     * @return string
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getElementHtml()
    {
        if ($checked = $this->getIsChecked()) {
            $this->setData('checked', true);
        } else {
            $this->unsetData('checked');
        }
        return parent::getElementHtml();
    }

    /**
     * Set check status of checkbox
     *
     * @param bool $value
     * @return Checkbox
     */
    public function setIsChecked($value = false)
    {
        $this->setData('checked', $value == '1');
        return $this;
    }

    /**
     * Return check status of checkbox
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsChecked()
    {
        $this->setChecked($this->getValue() == '1');
        return $this->getChecked();
    }
}
