<?php

namespace Splitit\PaymentGateway\Plugin\Backend\Magento\Config\Controller\Adminhtml\System\Config;

class Save extends \Magento\Config\Controller\Adminhtml\System\Config\Save
{
    /**
     * Around plugin for save configuration
     *
     * @param \Magento\Config\Controller\Adminhtml\System\Config\Save $subject
     * @param \Closure $proceed
     * @return $result
     */
    public function aroundExecute(
        \Magento\Config\Controller\Adminhtml\System\Config\Save $subject,
        \Closure $proceed
    ) {
        $section = $subject->getRequest()->getParam('section');
        $website = $subject->getRequest()->getParam('website');
        $store = $subject->getRequest()->getParam('store');

        $configData = [
            'section' => $section,
            'website' => $website,
            'store' => $store,
            'groups' => $subject->_getGroupsForSave()
        ];
        
        $flag = 0;
        if ($section == 'payment') {
            $ranges = $configData['groups']['splitit_payment']['fields']['ranges']['value'];
            if (isset($ranges)) {
                $arr = array();
                foreach ($ranges as $range) {
                    if ($range == "") {
                        break;
                    }
                    $to = $range['priceTo'];
                    $from = $range['priceFrom'];
                    if ($to < $from) {
                        $flag = 1;
                        break;
                    }
                    $arr [] = $from;
                    $arr [] = $to;
                }
                if (isset($arr)) {
                    $originalArr = $arr;
                    sort($arr);
                    if (count(array_unique($arr)) < count($arr)) {
                        $flag = 1;
                    } elseif ($arr != $originalArr) {
                        $flag =1;
                    }
                }
            }
        }

        if ($flag == 1) {
            $msg = 'Please add a valid, non overlapping range values for splitit installment range.';
            $subject->messageManager->addError($msg);
            $subject->_saveState($subject->getRequest()->getPost('config_state'));

            $resultRedirect = $subject->resultRedirectFactory->create();
            return $resultRedirect->setPath(
                'adminhtml/system_config/edit',
                [
                    '_current' => ['section', 'website', 'store'],
                    '_nosid' => true
                ]
            );
        }

        $result = $proceed();
        return $result;
    }
}

