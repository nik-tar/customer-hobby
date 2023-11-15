<?php

namespace Niktar\CustomerHobby\Controller\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Hobby extends Action implements HttpGetActionInterface
{
    /**
     * @param Context $context
     * @param Session $session
     * @param PageFactory $resultPageFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        Context $context,
        private readonly Session $session,
        private readonly PageFactory $resultPageFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly DataObjectHelper $dataObjectHelper
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $block = $resultPage->getLayout()->getBlock('customer_edit');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }

        $data = $this->session->getCustomerFormData(true);
        $customerId = $this->session->getCustomerId();
        $customerDataObject = $this->customerRepository->getById($customerId);
        if (!empty($data)) {
            $this->dataObjectHelper->populateWithArray(
                $customerDataObject,
                $data,
                \Magento\Customer\Api\Data\CustomerInterface::class
            );
        }

        $this->session->setCustomerData($customerDataObject);

        return $resultPage;
    }
}
