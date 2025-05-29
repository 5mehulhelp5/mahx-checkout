<?php

declare(strict_types=1);

namespace MageHx\MahxCheckout\ViewModel;

use MageHx\MahxCheckout\Data\AddressData;
use MageHx\MahxCheckout\Data\ValidationMapperData;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MageHx\MahxCheckout\Data\AddressFieldAttributes;
use MageHx\MahxCheckout\Enum\CheckoutForm;
use MageHx\MahxCheckout\Model\EventDispatcher;
use MageHx\MahxCheckout\Model\FormDataStorage;
use MageHx\MahxCheckout\Model\QuoteDetails;
use MageHx\MahxCheckout\Service\AddressFieldManager;
use MageHx\MahxCheckout\Service\PrepareAddressLines;

class BillingAddress implements ArgumentInterface
{
    private ?array $fields = null;

    public function __construct(
        private readonly QuoteDetails $quote,
        private readonly Json $jsonSerializer,
        private readonly CustomerSession $customerSession,
        private readonly EventDispatcher $eventDispatcher,
        private readonly FormDataStorage $formDataStorage,
        private readonly AddressFieldManager $addressFieldManager,
        private readonly PrepareAddressLines $prepareAddressLinesService,
    ) {
    }

    public function isBillingSameAsShipping(): bool
    {
        return $this->quote->isBillingSameAsShipping();
    }

    public function getBillingSameAsShippingValue(): bool
    {
        return (bool)($this->formDataStorage->getData('is_billing_same') ?? $this->isBillingSameAsShipping());
    }

    public function isEditing(): bool
    {
        return (bool)$this->formDataStorage->getData('is_edit');
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function isVirtualCart(): bool
    {
        return $this->quote->isVirtualQuote();
    }

    public function getBillingAddressLines(): array
    {
        $address = $this->isBillingSameAsShipping() ?
            $this->quote->getShippingAddress() : $this->quote->getBillingAddress();

        return $this->prepareAddressLinesService->getLinesOfAddress($address);
    }

    /**
    * @return AddressFieldAttributes[]
    */
    public function getAddressFields(): array
    {
        if ($this->fields !== null) {
            return $this->fields;
        }

        $this->fields = $this->addressFieldManager->getAddressFieldList(CheckoutForm::BILLING_ADDRESS->value);

        $this->eventDispatcher->dispatchBillingAddressFormFieldsPrepared(['fields' => $this->fields]);

        return $this->fields;
    }

    public function renderField(AddressFieldAttributes $fieldAttributes): string
    {
        $renderer = $this->addressFieldManager->getRenderForAddressField($fieldAttributes);
        $rendererData = new DataObject(['renderer' => $renderer]);

        $this->eventDispatcher->dispatchBillingAddressFieldRenderBefore(
            ['field_attributes' => $fieldAttributes, 'renderer_data' => $rendererData]
        );

        $fieldHtml = $rendererData->getData('renderer')->render($fieldAttributes);
        $fieldHtmlDataObject = new DataObject(['html' => $fieldHtml]);

        $this->eventDispatcher->dispatchBillingAddressFieldRenderAfter(
            ['field_attributes' => $fieldAttributes, 'fieldHtml' => $fieldHtmlDataObject]
        );

        return $fieldHtmlDataObject->getData('html');
    }

    public function getAddressFieldsJson(): string
    {
        return $this->addressFieldManager->prepareAddressFieldsDataForJs($this->getAddressFields());
    }

    public function canShowForm(): bool
    {
        $showForm = $this->formDataStorage->getData('show_form');

        if ($showForm === null) {
            return $this->quote->isVirtualQuote();
        }

        return (bool)$showForm;
    }

    // @todo before after events to modify rules
    public function getValidationJson(): string
    {
        $addressData = AddressData::from([
            'firstname' => '',
            'lastname' => '',
            'street' => [],
            'city' => '',
            'country_id' => '',
            'postcode' => '',
            'telephone' => '',
            'region' => '',
        ]);
        return $this->jsonSerializer->serialize(ValidationMapperData::from([
            'rules' => $addressData->rules(),
            'messages' => $addressData->messages(),
            'aliases' => $addressData->aliases(),
        ])->exportToJs());
    }
}
