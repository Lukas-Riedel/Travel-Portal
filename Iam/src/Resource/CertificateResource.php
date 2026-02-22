<?php
    namespace Iam\Resource;

    use Common\Resource\AbstractResource;
    use Iam\Service\Certificate\CertificateService;
    use Slim\App;
    use Slim\Psr7\Request;
    use Slim\Psr7\Response;
    
    class CertificateResource extends AbstractResource {

        private readonly CertificateService $certificateService;

        public function __construct(CertificateService $certificateService) {
            $this->certificateService = $certificateService;
        }

        public static function register(App $app, CertificateService $certificateService) : void {
            $resource = new self($certificateService);

            $app->group("/certificates", function($group) use($resource) {
                $group->get("/jwks", [$resource, "getJwksCertificate"]);
            });
        }

        public function getJwksCertificate(Request $request, Response $response, array $routeArguments) : mixed {
            return $this->certificateService->getJwksKeys();
        }
    }
?>