<?php
    namespace Service\Service\Category;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "RegionType",
        type: "string",
        description: "The type of the region"
    )]
    enum RegionType : string {
        case Geographical = "geographical";
        case GeographicalExtension = "geographicalExtension";
        case Composie = "composite";
    }
?>