<?php

namespace Niktar\CustomerHobby\Controller\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Phrase;

class EditHobby extends AbstractAccount implements CsrfAwareActionInterface, HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param Mapper $customerMapper
     * @param CustomerRepositoryInterface $customerRepository
     * @param Validator $formKeyValidator
     * @param Session $session
     * @param Escaper $escaper
     * @param CustomerExtractor $customerExtractor
     * @param AddressRegistry $addressRegistry
     */
    public function __construct(
        Context $context,
        private readonly Mapper $customerMapper,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly Validator $formKeyValidator,
        private readonly Session $session,
        private readonly Escaper $escaper,
        private readonly CustomerExtractor $customerExtractor,
        private readonly AddressRegistry $addressRegistry
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $validFormKey = $this->formKeyValidator->validate($this->getRequest());

        if ($validFormKey && $this->getRequest()->isPost()) {
            $customer = $this->getCustomerDataObject($this->session->getCustomerId());
            $customerCandidate = $this->populateNewCustomerDataObject($this->_request, $customer);

            try {
                // No need to validate customer address while editing customer profile
                $this->disableAddressValidation($customerCandidate);

                $this->customerRepository->save($customerCandidate);
                $updatedCustomer = $this->customerRepository->getById($customerCandidate->getId());

                $this->dispatchSuccessEvent($updatedCustomer);
                $this->messageManager->addSuccessMessage(__('You have updated your hobby.'));
                return $resultRedirect->setPath('customer/account');
            } catch (InvalidEmailOrPasswordException $e) {
                $this->messageManager->addErrorMessage($this->escaper->escapeHtml($e->getMessage()));
            } catch (UserLockedException $e) {
                $message = __(
                    'The account sign-in was incorrect or your account is disabled temporarily. '
                    . 'Please wait and try again later.'
                );
                $this->session->logout();
                $this->session->start();
                $this->messageManager->addErrorMessage($message);

                return $resultRedirect->setPath('customer/account/login');
            } catch (InputException $e) {
                $this->messageManager->addErrorMessage($this->escaper->escapeHtml($e->getMessage()));
                foreach ($e->getErrors() as $error) {
                    $this->messageManager->addErrorMessage($this->escaper->escapeHtml($error->getMessage()));
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t save the customer.'));
            }

            $this->session->setCustomerFormData($this->getRequest()->getPostValue());
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/edit');

        return $resultRedirect;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/edit');

        return new InvalidRequestException(
            $resultRedirect,
            [new Phrase('Invalid Form Key. Please refresh the page.')]
        );
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return null;
    }

    /**
     * Account editing action completed successfully event
     *
     * @param CustomerInterface $customerCandidateDataObject
     * @return void
     */
    private function dispatchSuccessEvent(CustomerInterface $customerCandidateDataObject)
    {
        $this->_eventManager->dispatch(
            'customer_account_edited',
            ['email' => $customerCandidateDataObject->getEmail()]
        );
    }

    /**
     * Get customer data object
     *
     * @param int $customerId
     *
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerDataObject($customerId)
    {
        return $this->customerRepository->getById($customerId);
    }

    /**
     * Create Data Transfer Object of customer candidate
     *
     * @param RequestInterface $inputData
     * @param CustomerInterface $currentCustomerData
     * @return CustomerInterface
     */
    private function populateNewCustomerDataObject(
        RequestInterface $inputData,
        CustomerInterface $currentCustomerData
    ) {
        $attributeValues = $this->customerMapper->toFlatArray($currentCustomerData);
        $customerDto = $this->customerExtractor->extract(
            'customer_account_edit',
            $inputData,
            $attributeValues
        );
        $customerDto->setId($currentCustomerData->getId());
        if (!$customerDto->getAddresses()) {
            $customerDto->setAddresses($currentCustomerData->getAddresses());
        }
        $customerDto->setEmail($currentCustomerData->getEmail());
        $customerDto->setFirstname($currentCustomerData->getFirstname());
        $customerDto->setLastname($currentCustomerData->getLastname());

        return $customerDto;
    }

    /**
     * Disable Customer Address Validation
     *
     * @param CustomerInterface $customer
     * @throws NoSuchEntityException
     */
    private function disableAddressValidation($customer)
    {
        foreach ($customer->getAddresses() as $address) {
            $addressModel = $this->addressRegistry->retrieve($address->getId());
            $addressModel->setShouldIgnoreValidation(true);
        }
    }
}
