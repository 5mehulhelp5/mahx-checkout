<?php

declare(strict_types=1);

namespace MageHx\MahxCheckout\Observer\BillingAddress;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Address;
use MageHx\MahxCheckout\Data\FormFieldConfig;
use MageHx\MahxCheckout\Model\Config;
use MageHx\MahxCheckout\Model\QuoteDetails;

/**
 * @event mahxcheckout_billing_address_form_fields_prepared
 */
class PopulateBillingAddressFormValues implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly QuoteDetails $quote,
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var FormFieldConfig[] $addressFields */
        /** @var DataObject $transport */
        $transport = $observer->getData('transport');
        $addressFields = $transport->getData('fields');
        $address = $this->quote->isBillingSameAsShipping() ?
            $this->quote->getShippingAddress() : $this->quote->getBillingAddress();
        $country = $address->getCountryId() ?: $this->config->getDefaultBillingCountry();
        $countryField = $addressFields['country_id'] ?? null;

        if ($countryField) {
            $countryField->value = $country;
        }

        /**
         * Only when billing !== shipping, we need to show values in the form input fields
         */
        if (!$this->quote->isBillingSameAsShipping() && !$address->getCustomerAddressId()) {
            $this->populateFieldWithAddressValues($addressFields, $address);
        }

        $transport->setData('fields', $addressFields);
    }

    private function populateFieldWithAddressValues(array $addressFields, Address $address): void
    {
        foreach ($addressFields as $field) {
            $value = $address->getData($field->name) ?: '';

            if ($field->name === 'street') {
                $value = $address->getStreet();
            }

            $field->value = $value;
        }
    }
}
