<?php
    namespace Core\Service\Expense;

    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class ExpenseServiceListener {

        private const SEND_VOUCHER_NOTIFICATIONS_ACTION_NAME = "SEND_VOUCHER_NOTIFICATIONS";
        private const SEND_VOUCHER_NOTIFICATIONS_ACTION_INTERVAL = 300;
        private const SEND_SUBSCRIPTION_NOTIFICATIONS_ACTION_NAME = "SEND_SUBSCRIPTION_NOTIFICATIONS";
        private const SEND_SUBSCRIPTION_NOTIFICATIONS_ACTION_INTERVAL = 300;

        private readonly ExpenseService $expenseService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly int $voucherNotificationThreshold;
        private readonly int $subscriptionNotificationThreshold;

        public function __construct(ExpenseService $expenseService, EventPublisher $eventPublisher, Scheduler $scheduler, int $voucherNotificationThreshold, int $subscriptionNotificationThreshold) {
            $this->expenseService = $expenseService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->voucherNotificationThreshold = $voucherNotificationThreshold;
            $this->subscriptionNotificationThreshold = $subscriptionNotificationThreshold;
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::SEND_VOUCHER_NOTIFICATIONS_ACTION_NAME, self::SEND_VOUCHER_NOTIFICATIONS_ACTION_INTERVAL)) {
                foreach ($this->expenseService->getVouchersForNotifications($this->voucherNotificationThreshold) as &$voucher) {
                    $this->eventPublisher->publish(Event::VoucherExpiring($voucher->getIssuer(), $voucher->getValue(), $voucher->getCurrency()->value, $voucher->getExpiration()));
                }
            }

            if ($this->scheduler->requestExecution(self::SEND_SUBSCRIPTION_NOTIFICATIONS_ACTION_NAME, self::SEND_SUBSCRIPTION_NOTIFICATIONS_ACTION_INTERVAL)) {
                foreach ($this->expenseService->getSubscriptionsForNotifications($this->subscriptionNotificationThreshold) as &$subscription) {
                    $this->eventPublisher->publish(Event::SubscriptionExpiring($subscription->getDescription(), $subscription->getExpiration()));
                }
            }
        }
    }
?>
