<?php

namespace Geidea\Payment\Model\ResourceModel;

use Magento\Framework\Option\ArrayInterface;

class EnvOptions implements ArrayInterface
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
                'value' => 'EGY-PROD',
                'label' => __('EGY-PROD')
            ],
            [
                'value' => 'EGY-PREPROD',
                'label' => __('EGY-PREPROD')
            ],
            [
                'value' => 'UAE-PROD',
                'label' => __('UAE-PROD')
            ],
            [
                'value' => 'UAE-PREPROD',
                'label' => __('UAE-PREPROD')
            ],
            [
                'value' => 'KSA-PROD',
                'label' => __('KSA-PROD')
            ],
            [
                'value' => 'KSA-PREPROD',
                'label' => __('KSA-PREPROD')
            ],
        ];
    }
}
