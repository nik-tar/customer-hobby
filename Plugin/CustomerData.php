<?php

namespace Niktar\CustomerHobby\Plugin;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Niktar\CustomerHobby\Setup\Patch\Data\AddCustomerHobbyAttribute;

class CustomerData
{
    /**
     * @param Session $customerSession
     * @param CurrentCustomer $currentCustomer
     * @param CustomerMetadataInterface $customerMetadata
     */
    public function __construct(
        private readonly Session $customerSession,
        private readonly CurrentCustomer $currentCustomer,
        private readonly CustomerMetadataInterface $customerMetadata
    ) {
    }

    /**
     * Add customer hobby to customer data
     *
     * @param \Magento\Customer\CustomerData\Customer $subject
     * @param array $result
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(
        \Magento\Customer\CustomerData\Customer $subject,
        array $result
    ) {
        /** unset customer first name  */
        if (!$this->customerSession->isLoggedIn()) {
            return $result;
        }
        $customer = $this->currentCustomer->getCustomer();
        $attributeValue = $customer->getCustomAttribute(AddCustomerHobbyAttribute::ATTRIBUTE_CODE_HOBBY)
            ?->getValue();
        if (empty($attributeValue)) {
            $result['hobby'] = $attributeValue;
            return $result;
        }

        $attributeMetadata = $this->getHobbyAttributeMetadata();
        $options = $attributeMetadata->getOptions();
        foreach ($options as $option) {
            if ($option->getValue() !== $attributeValue) {
                continue;
            }
            $result['hobby'] = __($option->getLabel());
            break;
        }

        return $result;
    }

    /**
     * @return AttributeMetadataInterface|null
     */
    private function getHobbyAttributeMetadata(): ?AttributeMetadataInterface
    {
        try {
            return $this->customerMetadata->getAttributeMetadata(AddCustomerHobbyAttribute::ATTRIBUTE_CODE_HOBBY);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
