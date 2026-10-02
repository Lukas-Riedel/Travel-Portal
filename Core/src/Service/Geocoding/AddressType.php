<?php
    namespace Core\Service\Geocoding;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "AddressType",
        type: "string",
        description: "The type of the address"
    )]
    enum AddressType : string {
        case Airport = "airport";
        case Stay = "stay";
        case Home = "home";
        case Other = "other";
    }
?>
