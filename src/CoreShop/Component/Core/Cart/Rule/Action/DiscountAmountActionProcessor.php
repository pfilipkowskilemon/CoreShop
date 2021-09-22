<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.org)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace CoreShop\Component\Core\Cart\Rule\Action;

use CoreShop\Component\Core\Cart\Rule\Applier\CartRuleApplierInterface;
use CoreShop\Component\Currency\Converter\CurrencyConverterInterface;
use CoreShop\Component\Currency\Model\CurrencyInterface;
use CoreShop\Component\Currency\Repository\CurrencyRepositoryInterface;
use CoreShop\Component\Order\Cart\Rule\Action\CartPriceRuleActionProcessorInterface;
use CoreShop\Component\Order\Model\AdjustmentInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\ProposalCartPriceRuleItemInterface;
use Webmozart\Assert\Assert;

class DiscountAmountActionProcessor implements CartPriceRuleActionProcessorInterface
{
    protected CurrencyConverterInterface $moneyConverter;
    protected CurrencyRepositoryInterface $currencyRepository;
    protected CartRuleApplierInterface $cartRuleApplier;

    public function __construct(
        CurrencyConverterInterface $moneyConverter,
        CurrencyRepositoryInterface $currencyRepository,
        CartRuleApplierInterface $cartRuleApplier
    ) {
        $this->moneyConverter = $moneyConverter;
        $this->currencyRepository = $currencyRepository;
        $this->cartRuleApplier = $cartRuleApplier;
    }

    public function applyRule(
        OrderInterface $cart,
        array $configuration,
        ProposalCartPriceRuleItemInterface $cartPriceRuleItem
    ): bool {
        $discount = $this->getDiscount($cart, $configuration);

        if ($discount <= 0) {
            return false;
        }

        $this->cartRuleApplier->applyDiscount($cart, $cartPriceRuleItem, $discount, $configuration['gross']);

        return true;
    }

    public function unApplyRule(
        OrderInterface $cart,
        array $configuration,
        ProposalCartPriceRuleItemInterface $cartPriceRuleItem
    ): bool {
        return true;
    }

    protected function getDiscount(OrderInterface $cart, array $configuration): int
    {
        $applyOn = isset($configuration['applyOn']) ? $configuration['applyOn'] : 'total';

        if ('total' === $applyOn) {
            $cartAmount = $cart->getTotal($configuration['gross']);
        } else {
            $cartAmount =
                $cart->getSubtotal($configuration['gross']) +
                $cart->getAdjustmentsTotal(AdjustmentInterface::CART_PRICE_RULE, $configuration['gross']);
        }

        $currency = $this->currencyRepository->find($configuration['currency']);
        $amount = $configuration['amount'];

        Assert::isInstanceOf($currency, CurrencyInterface::class);

        return $this->moneyConverter->convert(
            $this->getApplicableAmount($cartAmount, $amount),
            $currency->getIsoCode(),
            $cart->getCurrency()->getIsoCode()
        );
    }

    protected function getApplicableAmount(int $cartAmount, int $ruleAmount): int
    {
        return min($cartAmount, $ruleAmount);
    }
}
