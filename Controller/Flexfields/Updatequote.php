<?php

namespace Splitit\PaymentGateway\Controller\Flexfields;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Json\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class Updatequote extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
    */
    protected $resultPageFactory;

    /**
     * @var Data
    */
    protected $jsonHelper;

    /**
     * @var LoggerInterface
    */
    protected $logger;

    /**
     * @var CartRepositoryInterface
    */
    protected $quoteRepository;

    /**
     * Constructor
     *
     * @param Context  $context
     * @param PageFactory $resultPageFactory
     * @param Data $jsonHelper
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $jsonHelper,
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $postData = $this->_request->getParams();
        $quoteId = $postData['quoteId'];
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $totalAmount = bcdiv($quote->getGrandTotal(), 1, 2);
            return $this->jsonResponse($totalAmount);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
