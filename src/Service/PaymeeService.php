<?php

namespace App\Service;

use App\Entity\Plan;
use App\Entity\User;
use PayMee\Enums\CheckoutType;
use PayMee\Helpers\PayMeeCheckout;
use PayMee\Model\Shopper;

class PaymeeService
{
    public function __construct(
        private string $apiKey,
        private string $apiToken,
        private bool $sandbox
    ) {}

    /**
     * Create a PayMee transfer checkout and return the redirect URL.
     */
    public function createTransferCheckout(Plan $plan, User $user, string $callbackUrl): string
    {
        $shopper = new Shopper();
        $shopper
            ->withEmail($user->getEmail())
            ->withFullName(trim(($user->getName() ?? '') . ' ' . ($user->getLastname() ?? '')))
            ->withPhone('')
            ->withCpf('')
            ->withBranch('')
            ->withAccount('');

        $paymeeCheckout = new PayMeeCheckout($this->apiKey, $this->apiToken, $this->sandbox);

        $response = $paymeeCheckout
            ->withCurrency('TND')
            ->withAmount((float) $plan->getPrice())
            ->withReferenceCode('PLAN-' . $plan->getId() . '-USER-' . $user->getId() . '-' . time())
            ->withMaxAge(2880)
            ->withPaymentMethod('TRANSFER')
            ->withCallbackURL($callbackUrl)
            ->withShopper($shopper)
            ->create(CheckoutType::SEMI_TRANSPARENT, true);

        // Depending on SDK, response may be array or object. Adjust key if needed.
        if (is_array($response) && isset($response['checkoutUrl'])) {
            return $response['checkoutUrl'];
        }

        if (is_object($response) && isset($response->checkoutUrl)) {
            return $response->checkoutUrl;
        }

        // Fallback: let caller handle unexpected response structure.
        throw new \RuntimeException('Unexpected PayMee response structure');
    }
}
