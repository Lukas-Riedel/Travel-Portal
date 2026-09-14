<?php
    namespace Core\Service\Expense;

    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class ExpenseServiceListener {

        private const SEND_VOUCHER_NOTIFICATIONS_ACTION_NAME = "SEND_VOUCHER_NOTIFICATIONS";
        private const SEND_VOUCHER_NOTIFICATIONS_ACTION_INTERVAL = 300;

        private readonly ExpenseService $expenseService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly int $notificationThreshold;

        public function __construct(ExpenseService $expenseService, EventPublisher $eventPublisher, Scheduler $scheduler, int $notificationThreshold) {
            $this->expenseService = $expenseService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->notificationThreshold = $notificationThreshold;
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::SEND_VOUCHER_NOTIFICATIONS_ACTION_NAME, self::SEND_VOUCHER_NOTIFICATIONS_ACTION_INTERVAL)) {
                foreach ($this->expenseService->getVouchersForNotifications($this->notificationThreshold) as &$voucher) {
                    $this->eventPublisher->publish(Event::VoucherExpiring($voucher->getIssuer(), $voucher->getValue(), $voucher->getCurrency()->value, $voucher->getExpiration()));
                    $this->expenseService->resetVoucherLastNotification($voucher->getId());
                }
            }
        }
    }
?>
