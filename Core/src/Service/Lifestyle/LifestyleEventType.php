<?php
    namespace Core\Service\Lifestyle;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "LifestyleEventType",
        type: "string"
    )]
    enum LifestyleEventType : string {
        case Intake = "intake";
        case Expense = "expense";
        case Weight = "weight";
        case Steps = "steps";
        case BasalMetabolism = "basalMetabolism";
    }
?>
