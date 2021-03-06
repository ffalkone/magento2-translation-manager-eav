<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerEav\Mapper;

use Eurotext\RestApiClient\Response\Project\ItemGetResponse;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;

class AttributeItemGetMapper
{
    /**
     * @var AttributeOptionLabelInterfaceFactory
     */
    private $optionLabelFactory;

    /**
     * @var AttributeFrontendLabelInterfaceFactory
     */
    private $frontendLabelFactory;

    public function __construct(
        AttributeFrontendLabelInterfaceFactory $frontendLabelFactory,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory
    ) {
        $this->optionLabelFactory   = $optionLabelFactory;
        $this->frontendLabelFactory = $frontendLabelFactory;
    }

    public function map(
        ItemGetResponse $itemGetResponse,
        AttributeInterface $attribute,
        int $storeId
    ): AttributeInterface {
        $item = $itemGetResponse->getItemData();

        $label = (string)$item->getDataValue('label');
        if (!empty($label)) {
            $this->mapLabel($attribute, $label, $storeId);
        }

        $options = $item->getDataValue('options');
        if (is_array($options) && count($options) > 0) {
            $this->mapOptions($attribute, $options, $storeId);
        }

        return $attribute;
    }

    private function mapLabel(AttributeInterface $attribute, string $label, int $storeId)
    {
        // Set Attribute translation for Store
        $frontendLabels = $attribute->getFrontendLabels();

        // @todo find existing frontendLabel and overwrite

        /** @var AttributeFrontendLabelInterface $frontendLabel */
        $frontendLabel = $this->frontendLabelFactory->create();
        $frontendLabel->setLabel($label);
        $frontendLabel->setStoreId($storeId);

        $frontendLabels[] = $frontendLabel;
        $attribute->setFrontendLabels($frontendLabels);
    }

    private function mapOptions(AttributeInterface $attribute, array $translatedOptions, int $storeId)
    {
        $options = $attribute->getOptions();

        foreach ($translatedOptions as $optionValue => $optionLabel) {
            $option = $this->findAttributeOptionByValue($options, (string)$optionValue);
            if ($option === null) {
                // Option not found, might have been deleted, so we skip it
                continue;
            }

            $storeLabels = $option->getStoreLabels();

            // @todo find existing frontendLabel and overwrite

            /** @var AttributeOptionLabelInterface $storeLabel */
            $storeLabel = $this->optionLabelFactory->create();
            $storeLabel->setLabel($optionLabel);
            $storeLabel->setStoreId($storeId);

            $storeLabels[] = $storeLabel;

            $option->setStoreLabels($storeLabels);
        }

        $attribute->setOptions($options);
    }

    /**
     * @param AttributeOptionInterface[] $options
     * @param string $value
     *
     * @return AttributeOptionInterface|null
     */
    private function findAttributeOptionByValue(array $options, string $value)
    {
        /** @var AttributeOptionInterface[] $options */
        foreach ($options as $option) {
            if ($option->getValue() === $value) {
                return $option;
            }
        }

        return null;
    }
}