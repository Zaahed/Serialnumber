<?php

namespace Zaahed\Serialnumber\Model\Export;

use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use TCPDFFactory as PdfFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Zaahed\Serialnumber\Model\Config\PdfLabel as Config;

class ConvertToPdfLabels
{
    /**
     * Original page width based on A7 format.
     */
    private const ORIGINAL_PAGE_WIDTH = 105;
    /**
     * Original barcode width based on A7 format.
     */
    private const ORIGINAL_BARCODE_WIDTH = 90;
    /**
     * Original barcode height based on A7 format.
     */
    private const ORIGINAL_BARCODE_HEIGHT = 30;

    /**
     * Maximum barcode width.
     */
    private const MAX_BARCODE_WIDTH = 150;

    /**
     * Maximum barcode height.
     */
    private const MAX_BARCODE_HEIGHT = 50;

    /**
     * @var PdfFactory
     */
    private $pdfFactory;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $directory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param PdfFactory $pdfFactory
     * @param Filesystem $filesystem
     * @param Filter $filter
     * @param UrlInterface $urlBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param Config $config
     * @param AttributeRepositoryInterface $attributeRepository
     * @throws FileSystemException
     */
    public function __construct(
        PdfFactory $pdfFactory,
        Filesystem $filesystem,
        Filter $filter,
        UrlInterface $urlBuilder,
        ProductRepositoryInterface $productRepository,
        Config $config,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->pdfFactory = $pdfFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->filter = $filter;
        $this->urlBuilder = $urlBuilder;
        $this->productRepository = $productRepository;
        $this->config = $config;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Get PDF file.
     *
     * @return array
     */
    public function getPdfFile()
    {
        $component = $this->filter->getComponent();

        // md5() here is not for cryptographic use.
        // phpcs:ignore Magento2.Security.InsecureFunction
        $name = md5(microtime());
        $file = 'export/'. $component->getName() . $name . '.pdf';

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $stream->write(
            $this->generatePdfOutput()
        );

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ];
    }

    /**
     * Generate PDF output.
     *
     * @return string
     */
    private function generatePdfOutput()
    {
        $pdf = $this->pdfFactory->create([
            'orientation' => 'landscape',
            'format'   => $this->config->getPageSize()
        ]);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(1, 1, 0);
        $pdf->SetFontSize($this->config->getFontSize());

        $pageWidth = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();
        $scaleFactor = $pageWidth / self::ORIGINAL_PAGE_WIDTH;

        $barcodeWidth = min(self::ORIGINAL_BARCODE_WIDTH * $scaleFactor,
            self::MAX_BARCODE_WIDTH);
        $barcodeHeight = min(self::ORIGINAL_BARCODE_HEIGHT * $scaleFactor,
            self::MAX_BARCODE_HEIGHT);
        $barcodeXPosition = ($pageWidth - $barcodeWidth) / 2;
        $barcodeYPosition = $pageHeight - $barcodeHeight - 10;

        $component = $this->filter->getComponent();
        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $component->getContext()->getDataProvider();
        $items = $dataProvider->getSearchResult()->getItems();

        foreach ($items as $item) {
            $serialnumber = $item->getSerialnumber();
            $html = sprintf(
                '<span style="text-align: center">%s</span><br>',
                $serialnumber);

            if ($item->getProductId() !== null) {
                $html .= $this->getProductAttributesHtml($item->getProductId());
            }

            $pdf->addPage();
            $pdf->writeHTML($html);

            $pdf->write1DBarcode($serialnumber,
                'C39',
                $barcodeXPosition,
                $barcodeYPosition,
                $barcodeWidth,
                $barcodeHeight);
        }

        return $pdf->Output();
    }

    /**
     * Get HTML for product attributes.
     *
     * @param int $productId
     * @return string
     */
    private function getProductAttributesHtml($productId)
    {
        $result = '';

        $attributes = $this->config->getProductAttributes();
        $product = $this->productRepository->getById($productId);

        foreach ($attributes as $attribute) {
            $value = $this->getAttributeValue($product, $attribute);
            $label = $this->getAttributeLabel($attribute);
            if ($value === null || trim($value) === '') {
                continue;
            }

            $result .= $this->config->hideAttributeLabels() ?
                "<br><span>$value</span>" :
                "<br><b>$label: </b><span>$value</span>";
        }

        return $result;
    }

    /**
     * Get attribute value for a product.
     *
     * @param ProductInterface $product
     * @param string $code
     * @return string|null
     */
    private function getAttributeValue(ProductInterface $product, string $code)
    {
        $attribute = $this->attributeRepository->get(
            Product::ENTITY,
            $code
        );
        $input = $attribute->getFrontendInput();
        if ($input === 'select'||
            $input === 'multiselect' ||
            $attribute->getSourceModel() != '') {
            return $product->getAttributeText($code);
        }

        return $product->getData($code);
    }

    /**
     * Get attribute label by attribute code.
     *
     * @param string $code
     * @return string
     */
    private function getAttributeLabel($code)
    {
        $attribute = $this->attributeRepository->get(
            Product::ENTITY,
            $code
        );

        return $attribute->getDefaultFrontendLabel();
    }
}