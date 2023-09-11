<?php

namespace Zaahed\Serialnumber\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class PdfLabel
{
    const PDF_LABEL_PRODUCT_ATTRIBUTES = 'serialnumber/pdf_label/product_attributes';
    const PDF_LABEL_ATTRIBUTE_SETS = 'serialnumber/pdf_label/attribute_sets';
    const PDF_LABEL_PAGE_SIZE = 'serialnumber/pdf_label/page_size';
    const PDF_LABEL_FONT_SIZE = 'serialnumber/pdf_label/font_size';
    const PDF_LABEL_HIDE_ATTRIBUTE_LABELS = 'serialnumber/pdf_label/hide_attribute_labels';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get product attributes for label.
     *
     * @return array
     */
    public function getProductAttributes(): array
    {
        $attributes = $this->scopeConfig->getValue(
            self::PDF_LABEL_PRODUCT_ATTRIBUTES,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        return explode(',', $attributes);
    }

    /**
     * Get attribute sets. Used for filtering product attributes in config.
     *
     * @return array
     */
    public function getAttributeSets(): array
    {
        $attributeSets = $this->scopeConfig->getValue(
            self::PDF_LABEL_ATTRIBUTE_SETS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        return explode(',', $attributeSets);
    }

    public function getPageSize(): string
    {
        return $this->scopeConfig->getValue(
            self::PDF_LABEL_PAGE_SIZE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get font size for PDF label.
     *
     * @return int
     */
    public function getFontSize(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::PDF_LABEL_FONT_SIZE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Hide attribute labels on PDF label. Only show attribute values.
     *
     * @return bool
     */
    public function hideAttributeLabels(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::PDF_LABEL_HIDE_ATTRIBUTE_LABELS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }
}