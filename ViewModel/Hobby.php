<?php
declare(strict_types=1);

namespace Niktar\CustomerHobby\ViewModel;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Api\Data\OptionInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Niktar\CustomerHobby\Setup\Patch\Data\AddCustomerHobbyAttribute;

class Hobby implements ArgumentInterface
{
    public function __construct(
        private readonly CustomerMetadataInterface $customerMetadata,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly Session $customerSession
    ) {
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return (bool)$this->getAttribute()?->isRequired();
    }

    /**
     * @return Phrase|string
     */
    public function getStoreLabel(): Phrase|string
    {
        return $this->getAttribute() === null
            ? ''
            : __($this->getAttribute()->getStoreLabel());
    }

    /**
     * @return OptionInterface[]
     */
    public function getHobbyOptions(): array
    {
        return $this->getAttribute()->getOptions();
    }

    /**
     * @return string
     */
    public function getHobby(): string
    {
        try {
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
            return (string)$customer->getCustomAttribute(AddCustomerHobbyAttribute::ATTRIBUTE_CODE_HOBBY)
                ?->getValue();
        } catch (LocalizedException $e) {
            return '';
        }
    }

    /**
     * @return AttributeMetadataInterface|null
     */
    private function getAttribute(): ?AttributeMetadataInterface
    {
        try {
            return $this->customerMetadata->getAttributeMetadata(AddCustomerHobbyAttribute::ATTRIBUTE_CODE_HOBBY);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
