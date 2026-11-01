<?php
    use Core\Client\Calendar\Calendar;
    use Core\Event\Event;

    $eventPublisher->publish(Event::CalendarWatchRenewing(Calendar::CarRentals->value));
?>
