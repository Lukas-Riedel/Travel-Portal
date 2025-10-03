<?php
    namespace Core\Resource;

    use Common\Resource\AbstractResource;
    use Core\Event\EventPublisher;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    
    class EventResource extends AbstractResource {

        private readonly EventPublisher $eventPublisher;

        public function __construct(EventPublisher $eventPublisher) {
            $this->eventPublisher = $eventPublisher;
        }

        public static function register(App $app, EventPublisher $eventPublisher) : void {
            $resource = new self($eventPublisher);

            $app->group("/events", function($group) use($resource) {
                $group->post("", [$resource, "createEvent"]);
                $group->post("/webhook", [$resource, "createWebhookEvent"]);
            });
        }

        public function createEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $this->requireAdmin($request);

            $name = $this->requireJsonBodyField($request, "name");
            $args = $this->getJsonBodyField($request, "args");

            $this->eventPublisher->publishRawEvent($name, $args);

            return null;
        }
        
        public function createWebhookEvent(Request $request, Response $response, array $routeArguments) : mixed {
            $eventId = $this->requireQueryParameter($request, "eventId");

            $this->eventPublisher->publishStoredEvent($eventId);

            return null;
        }
    }
?>