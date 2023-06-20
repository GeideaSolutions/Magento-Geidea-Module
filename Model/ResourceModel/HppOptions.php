<?php

namespace Geidea\Payment\Model\ResourceModel;

use Magento\Framework\Option\ArrayInterface;

class HppOptions implements ArrayInterface
{
    /**
     * Get options for dropdown
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'compressed',
                'label' => __('Compressed')
            ],
            [
                'value' => 'simple',
                'label' => __('Simple')
            ]
        ];
    }
}
