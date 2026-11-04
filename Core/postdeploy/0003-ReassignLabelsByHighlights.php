<?php
    use Core\Event\Event;

    foreach ($labelService->getAllLabels(array()) as &$label) {
        $eventPublisher->publish(Event::LabelInvalidated($label->getId()));
    }
?>
