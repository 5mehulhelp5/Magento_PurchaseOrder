<?php
declare(strict_types=1);

namespace Punchout2Go\PurchaseOrder\Model\QuoteElementProvider;

use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory;
use Magento\Quote\Model\Quote\Address;
use Punchout2Go\PurchaseOrder\Api\AddressConverterInterface;
use Punchout2Go\PurchaseOrder\Api\PunchoutData\QuoteInterface;
use Punchout2Go\PurchaseOrder\Api\QuoteBuildContainerInterface;
use Punchout2Go\PurchaseOrder\Api\QuoteElementHandlerInterface;
use Punchout2Go\PurchaseOrder\Helper\Data;

/**
 * Class ShippingHandler
 * @package Punchout2Go\PurchaseOrder\Model\QuoteElementProvider
 */
class ShippingHandler implements QuoteElementHandlerInterface
{
    /**
     * @var TotalsInformationInterfaceFactory
     */
    protected $factory;

    /**
     * @var AddressConverterInterface
     */
    protected $addressConverter;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     * @param AddressConverterInterface $addressConverter
     * @param TotalsInformationInterfaceFactory $factory
     */
    public function __construct(
        Data $helper,
        AddressConverterInterface $addressConverter,
        TotalsInformationInterfaceFactory $factory
    ) {
        $this->helper = $helper;
        $this->factory = $factory;
        $this->addressConverter = $addressConverter;
    }

    /**
     * @param QuoteBuildContainerInterface $builder
     * @param QuoteInterface $punchoutQuote
     */
    public function handle(QuoteBuildContainerInterface $builder, QuoteInterface $punchoutQuote): void
    {
        /** @var TotalsInformationInterface $shippingInformation */
        $shippingInformation = $this->factory->create();
        $shippingAddress = $this->addressConverter->toQuoteAddress($punchoutQuote->getAddressByType(Address::ADDRESS_TYPE_SHIPPING));
        $shippingAddress
            ->setDiscountAmount((float)$punchoutQuote->getDiscount())
            ->setBaseDiscountAmount((float)$punchoutQuote->getDiscount())
            ->setDiscountDescription($punchoutQuote->getDiscountTitle());
        $shippingInformation->setAddress($shippingAddress);
        if (!$this->helper->isAllowedProvidedShipping($punchoutQuote->getStoreId())) {
            $builder->setShippingTotals($shippingInformation);
            return;
        }
        $shipping = $punchoutQuote->getShipping();
        if (!$shipping->getShippingMethod()) {
            $builder->setShippingTotals($shippingInformation);
            return;
        }
        list($carrier, $method) = explode('_', $shipping->getShippingMethod());
        $shippingInformation->setShippingMethodCode($method);
        $shippingInformation->setShippingCarrierCode($carrier);
        $builder->setShippingTotals($shippingInformation);
    }
}