<?php

declare(strict_types=1);

/*
 * CoreShop
 *
 * This source file is available under two different licenses:
 *  - GNU General Public License version 3 (GPLv3)
 *  - CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.org)
 * @license    https://www.coreshop.org/license     GPLv3 and CCL
 *
 */

namespace CoreShop\Bundle\OrderBundle\Controller;

use Carbon\Carbon;
use CoreShop\Bundle\ResourceBundle\Controller\PimcoreController;
use CoreShop\Bundle\WorkflowBundle\Manager\StateMachineManager;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\OrderPaymentInterface;
use CoreShop\Component\Order\Repository\OrderRepositoryInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Payment\Model\PaymentProviderInterface;
use CoreShop\Component\Payment\PaymentTransitions;
use CoreShop\Component\Payment\Repository\PaymentProviderRepositoryInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Resource\TokenGenerator\UniqueTokenGenerator;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrderPaymentController extends PimcoreController
{
    public function updateStateAction(Request $request): JsonResponse
    {
        $payment = $this->getPaymentRepository()->find($this->getParameterFromRequest($request, 'id'));
        $transition = $this->getParameterFromRequest($request, 'transition');

        if (!$payment instanceof PaymentInterface) {
            return $this->viewHandler->handle(['success' => false, 'message' => 'invalid payment']);
        }

        //apply state machine
        $workflow = $this->getStateMachineManager()->get($payment, 'coreshop_payment');
        if (!$workflow->can($payment, $transition)) {
            return $this->viewHandler->handle(['success' => false, 'message' => 'this transition is not allowed.']);
        }

        $workflow->apply($payment, $transition);

        return $this->viewHandler->handle(['success' => true]);
    }

    public function addPaymentAction(Request $request): JsonResponse
    {
        //TODO: Use Form here

        $orderId = $this->getParameterFromRequest($request, 'o_id');
        $order = $this->getSaleRepository()->find($orderId);
        $amount = (int) round($this->getParameterFromRequest($request, 'amount', 0) * $this->container->getParameter('coreshop.currency.decimal_factor'));

        $paymentProviderId = $this->getParameterFromRequest($request, 'paymentProvider');

        if (!$order instanceof OrderInterface) {
            return $this->viewHandler->handle(['success' => false, 'message' => 'Order with ID "' . $orderId . '" not found']);
        }

        $payments = $this->getPaymentRepository()->findForPayable($order);
        $paymentProvider = $this->getPaymentProviderRepository()->find($paymentProviderId);
        $totalPayed = array_sum(array_map(static function (PaymentInterface $payment) {
            $state = $payment->getState();
            if ($state === PaymentInterface::STATE_CANCELLED ||
                $state === PaymentInterface::STATE_FAILED ||
                $state === PaymentInterface::STATE_REFUNDED) {
                return 0;
            }

            return $payment->getTotalAmount();
        }, $payments));

        if ($paymentProvider instanceof PaymentProviderInterface) {
            $totalPaymentWouldBe = $totalPayed + $amount;

            if ($totalPaymentWouldBe > $order->getTotal()) {
                return $this->viewHandler->handle([
                    'success' => false,
                    'message' => 'Payed Amount is greater than order amount',
                ]);
            }

            $tokenGenerator = new UniqueTokenGenerator(true);
            $uniqueId = $tokenGenerator->generate(15);

            $objectOrderNumber = $order->getOrderNumber();
            if(empty($objectOrderNumber))
                $objectOrderNumber = $order->getQuoteNumber();
            $orderNumber = preg_replace('/[^A-Za-z0-9\-_]/', '', str_replace(' ', '_', $objectOrderNumber)) . '_' . $uniqueId;

            /**
             * @var PaymentInterface $payment
             */
            $payment = $this->getPaymentFactory()->createNew();
            $payment->setNumber($orderNumber);
            $payment->setPaymentProvider($paymentProvider);

            if (method_exists($payment, 'setCurrency')) {
                $payment->setCurrency($order->getBaseCurrency());
            }

            $payment->setTotalAmount($amount);
            $payment->setState(PaymentInterface::STATE_NEW);
            $payment->setDatePayment(Carbon::now());

            if ($payment instanceof OrderPaymentInterface) {
                $payment->setOrder($order);
            }

            $this->getEntityManager()->persist($payment);
            $this->getEntityManager()->flush();

            $workflow = $this->getStateMachineManager()->get($payment, 'coreshop_payment');
            $workflow->apply($payment, PaymentTransitions::TRANSITION_PROCESS);

            return $this->viewHandler->handle([
                'success' => true,
                'totalPayed' => $totalPayed,
            ]);
        }

        return $this->viewHandler->handle(
            [
                'success' => false,
                'message' => sprintf('Payment Provider %s not found', $this->getParameterFromRequest($request, 'paymentProvider')),
            ],
        );
    }

    private function getPaymentRepository(): PaymentRepositoryInterface
    {
        return $this->get('coreshop.repository.payment');
    }

    private function getEntityManager(): EntityManager
    {
        return $this->get('doctrine.orm.entity_manager');
    }

    private function getPaymentProviderRepository(): PaymentProviderRepositoryInterface
    {
        return $this->get('coreshop.repository.payment_provider');
    }

    private function getPaymentFactory(): FactoryInterface
    {
        return $this->get('coreshop.factory.payment');
    }

    protected function getStateMachineManager(): StateMachineManager
    {
        return $this->get('coreshop.state_machine_manager');
    }

    protected function getSaleRepository(): OrderRepositoryInterface
    {
        return $this->get('coreshop.repository.order');
    }
}
