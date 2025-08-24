<?php

declare(strict_types=1);

namespace MageHx\MahxCheckout\ViewModel;

use MageHx\MahxCheckout\Model\CheckoutDataStorage;
use MageHx\MahxCheckout\Model\QuoteDetails;
use MageHx\MahxCheckout\Service\CustomerAddressService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class ShowBillingForm implements ArgumentInterface
{
    public function __construct(
        private readonly QuoteDetails $quote,
        private readonly CustomerSession $customerSession,
        private readonly CheckoutDataStorage $checkoutDataStorage,
        private readonly CustomerAddressService $customerAddressService,
    ) {}

    public function canShowForm(): bool
    {
        if ($this->hasShowFormData()) {
            return (bool) $this->getShowFormData();
        }

        if ($this->quote->isBillingSameAsShipping()) {
            return false;
        }

        return $this->customerSession->isLoggedIn() && !$this->customerAddressService->isCurrentCustomerHoldsAddress();
    }

    public function canShowCards(): bool
    {
        if ($this->hasShowCardsData()) {
            return (bool)$this->getShowCardsData();
        }

        return false;
    }

    public function isEditing(): bool
    {
        return $this->getShowCardsData() || $this->getShowFormData();
    }

    public function currentCustomerHasAddress(): bool
    {
        return $this->customerAddressService->isCurrentCustomerHoldsAddress();
    }

    public function editFormRequestParams(): array
    {
        $customerHasAddress = (int)$this->currentCustomerHasAddress();

        return [
            'show_form' => $this->hasShowFormData() ? $this->getShowFormData() : (int)!$customerHasAddress ,
            'show_cards' => $this->hasShowCardsData() ? $this->getShowCardsData() : $customerHasAddress,
        ];
    }

    public function getShowFormData(): int
    {
        return (int) ($this->checkoutDataStorage->getData('show_form') ?? 0);
    }

    public function getShowCardsData(): int
    {
        return (int) ($this->checkoutDataStorage->getData('show_cards') ?? 0);
    }

    private function hasShowFormData(): bool
    {
        return $this->checkoutDataStorage->hasData('show_form');
    }

    private function hasShowCardsData(): bool
    {
        return $this->checkoutDataStorage->hasData('show_cards');
    }
}
