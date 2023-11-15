<?php

namespace Niktar\CustomerHobby\Model\Resolver;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Niktar\CustomerHobby\Setup\Patch\Data\AddCustomerHobbyAttribute;

class GetHobby implements ResolverInterface
{
    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var CustomerInterface $customer */
        $customer = $value['model'];

        /* Get customer custom attribute value */
        $hobby = $customer->getCustomAttribute(AddCustomerHobbyAttribute::ATTRIBUTE_CODE_HOBBY)
            ?->getValue();

        return $hobby;
    }

}
