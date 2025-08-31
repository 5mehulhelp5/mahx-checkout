<?php

declare(strict_types=1);

namespace MageHx\MahxCheckout\Model\Theme;

use MageHx\MahxCheckout\Data\CheckoutStepData;
use MageHx\MahxCheckout\Model\StepManager\CheckoutStepPool;
use MageHx\MahxCheckout\Model\StepManager\CheckoutStepPoolFactory;

abstract class CheckoutThemeAbstract implements CheckoutThemeInterface
{

    /**
     * @var CheckoutStepData[]
     */
    protected array $steps = [];

    /** @var CheckoutThemeInterface[] */
    protected array $parents = [];
    protected ?CheckoutStepPool $stepPool = null;

    public function __construct(
        private readonly CheckoutStepPoolFactory $stepPoolFactory,
    ) {
    }

    public function init(): void
    {
        $this->stepPool = $this->stepPoolFactory->create();
        $this->stepPool->loadStepsForTheme($this);
        $this->steps = $this->stepPool->getCheckoutStepsData();
    }

    public function setParentThemes(array $parentThemes): self
    {
        $this->parents = $parentThemes;
        return $this;
    }

    public function getParentThemes(): array
    {
        return $this->parents;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function findStepByName(string $name): ?CheckoutStepData
    {
        return $this->steps[$name] ?? null;
    }

    public function getInitialStep(): ?CheckoutStepData
    {
        foreach ($this->steps as $step) {
            if ($step->isDefault) {
                return $step;
            }
        }

        return null;
    }

    public function getStepAfter(string|CheckoutStepData $step): ?CheckoutStepData
    {
        $target = is_string($step) ? $this->findStepByName($step) : $step;
        $found = false;

        foreach ($this->steps as $current) {
            if ($found) {
                return $current;
            }
            if ($current === $target) {
                $found = true;
            }
        }

        return null;
    }

    public function isLastStep(string|CheckoutStepData $step): bool
    {
        $last = end($this->steps);
        $stepName = is_string($step) ? $step : $step->name;

        return $last && $stepName === $last->name;
    }

    public function getStepsSaveDataUrlList(): array
    {
        $urls = [];

        foreach ($this->getSteps() as $step) {
            $urls[$step->name] = $step->saveDataUrl;
        }

        return $urls;
    }

    public function hasSaveDataUrl(string $url): bool
    {
        foreach ($this->getStepsSaveDataUrlList() as $saveUrl) {
            if (str_contains($saveUrl, $url)) {
                return true;
            }
        }

        return false;
    }

    public function getParentCode(): ?string
    {
        return null;
    }
}
