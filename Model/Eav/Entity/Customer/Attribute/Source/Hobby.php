<?php
declare(strict_types=1);

namespace Niktar\CustomerHobby\Model\Eav\Entity\Customer\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Data\OptionSourceInterface;

class Hobby extends AbstractSource implements OptionSourceInterface
{
    /**
     * @var array|null
     */
    private ?array $options = null;

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = [
            ['label' => __('-- Please Select --'), 'value' => ''],
            ['label' => __('Yoga'), 'value' => 'yoga'],
            ['label' => __('Traveling'), 'value' => 'traveling'],
            ['label' => __('Hiking'), 'value' => 'hiking']
        ];

        return $this->options;
    }

    /**
     * @inheritDoc
     */
    public function getAllOptions(): array
    {
        return $this->toOptionArray();
    }
}
