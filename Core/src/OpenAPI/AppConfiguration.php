<?php
    namespace Core\OpenAPI;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "DynamicLabelsConfiguration",
        type: "array",
        description: "The dynamic labels configuration",
        items: new OA\Items(
            type: "object",
            required: ["name", "interval"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "Last year"),
                new OA\Property(property: "interval", type: "integer", example: 31536000)
            ]
        )
    )]
    #[OA\Schema(
        schema: "HomeLocationConfiguration",
        type: "object",
        description: "The home location configuration",
        required: ["country", "latitude", "longitude", "timezone", "countryCode"],
        properties: [
            new OA\Property(property: "country", type: "string", example: "Czechia"),
            new OA\Property(property: "latitude", type: "number", format: "float", example: 50.0755),
            new OA\Property(property: "longitude", type: "number", format: "float", example: 14.4378),
            new OA\Property(property: "timezone", type: "string", example: "Europe/Prague"),
            new OA\Property(property: "countryCode", type: "string", example: "CZ")
        ]
    )]
    #[OA\Schema(
        schema: "ExpensifyConfiguration",
        type: "object",
        description: "The expensify configuration",
        required: ["mainCurrency"],
        properties: [
            new OA\Property(property: "mainCurrency", ref: "#/components/schemas/ExpenseCurrency")
        ]
    )]
    #[OA\Schema(
        schema: "CalendarConfiguration",
        type: "object",
        description: "The calendar configuration",
        properties: [
            new OA\Property(property: "stays", type: "string", example: "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics"),
            new OA\Property(property: "trips", type: "string", example: "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics"),
            new OA\Property(property: "places", type: "string", example: "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics"),
            new OA\Property(property: "flights", type: "string", example: "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics"),
            new OA\Property(property: "watchedFlights", type: "string", example: "https://calendar.google.com/calendar/ical/example%40group.calendar.google.com/private-token/basic.ics")
        ]
    )]
    #[OA\Schema(
        schema: "TimeTrackingConfiguration",
        type: "object",
        description: "The time tracking configuration",
        required: ["currentFte", "expectedOvertimePerDay", "openingBalance"],
        properties: [
            new OA\Property(property: "currentFte", type: "number", format: "float", example: 1.0),
            new OA\Property(property: "expectedOvertimePerDay", type: "number", format: "float", example: 1.6),
            new OA\Property(
                property: "openingBalance",
                type: "object",
                required: ["tenure", "selfcare", "vacation"],
                properties: [
                    new OA\Property(property: "tenure", type: "number", format: "float", example: 0),
                    new OA\Property(property: "selfcare", type: "number", format: "float", example: 12.8),
                    new OA\Property(property: "vacation", type: "number", format: "float", example: 160)
                ]
            )
        ]
    )]
    #[OA\Schema(
        schema: "GenerativeContentPromptConfiguration",
        type: "object",
        description: "The generative content prompt configuration",
        properties: [
            new OA\Property(property: "placeExcerpt", type: "string", example: "Write a travel blog article about {name} ({country}) in region {region}."),
            new OA\Property(property: "alternativeAddress", type: "string", example: "Find an alternative name for the place \"{address}\" for use with the Geocoding API."),
            new OA\Property(property: "tripHighlightsSelecting", type: "string", example: "CLIP prompt for a trip covering: {places}."),
            new OA\Property(property: "yearHighlightsSelecting", type: "string", example: "CLIP prompt for the year's best moments from: {places}."),
            new OA\Property(property: "placeHighlightsSelecting", type: "string", example: "CLIP prompt for {name} in {country}."),
            new OA\Property(property: "categoryHighlightsSelecting", type: "string", example: "CLIP prompt for the region {name} containing: {places}.")
        ]
    )]
    #[OA\Schema(
        schema: "HighlightConfiguration",
        type: "object",
        description: "The highlight configuration",
        properties: [
            new OA\Property(
                property: "attribute",
                type: "object",
                properties: [
                    new OA\Property(property: "sky", type: "array", items: new OA\Items(ref: "#/components/schemas/HighlightAttributeConfigurationOption")),
                    new OA\Property(property: "shadows", type: "array", items: new OA\Items(ref: "#/components/schemas/HighlightAttributeConfigurationOption")),
                    new OA\Property(property: "atmosphere", type: "array", items: new OA\Items(ref: "#/components/schemas/HighlightAttributeConfigurationOption")),
                    new OA\Property(property: "impression", type: "array", items: new OA\Items(ref: "#/components/schemas/HighlightAttributeConfigurationOption")),
                    new OA\Property(property: "composition", type: "array", items: new OA\Items(ref: "#/components/schemas/HighlightAttributeConfigurationOption")),
                    new OA\Property(property: "circumstances", type: "array", items: new OA\Items(ref: "#/components/schemas/HighlightAttributeConfigurationOption"))
                ]
            ),
            new OA\Property(property: "negativeTerms", type: "array", items: new OA\Items(type: "string", example: "macro photography"))
        ]
    )]
    #[OA\Schema(
        schema: "HighlightAttributeConfigurationOption",
        type: "object",
        required: ["id", "value"],
        properties: [
            new OA\Property(property: "id", type: "string", example: "good"),
            new OA\Property(property: "text", type: "string", example: "Photo with a clear bright blue sky."),
            new OA\Property(property: "value", type: "integer", example: 100)
        ]
    )]
    #[OA\Schema(
        schema: "EphemeralTasksConfiguration",
        type: "array",
        description: "The ephemeral tasks configuration",
        items: new OA\Items(
            type: "object",
            required: ["source", "title", "text", "priority", "trigger"],
            properties: [
                new OA\Property(property: "source", type: "string", example: "flight.scheduled"),
                new OA\Property(property: "title", type: "string", example: "Upcoming flight"),
                new OA\Property(property: "text", type: "string", example: "Flight {flight} departs at {formattedTime} local time."),
                new OA\Property(property: "priority", ref: "#/components/schemas/TaskPriority"),
                new OA\Property(
                    property: "trigger",
                    type: "object",
                    required: ["seconds"],
                    properties: [
                        new OA\Property(property: "seconds", type: "integer", example: 3600)
                    ]
                ),
                new OA\Property(property: "notificationInterval", type: "integer", example: 259200),
                new OA\Property(property: "actionable", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Schema(
        schema: "AgentConfiguration",
        type: "object",
        description: "The Agent configuration",
        additionalProperties: true
    )]
    #[OA\Schema(
        schema: "AppConfiguration",
        type: "object",
        description: "An object representing the application configuration",
        additionalProperties: true,
        properties: [
            new OA\Property(property: "dynamicLabels", ref: "#/components/schemas/DynamicLabelsConfiguration"),
            new OA\Property(property: "homeLocation", ref: "#/components/schemas/HomeLocationConfiguration"),
            new OA\Property(property: "expensify", ref: "#/components/schemas/ExpensifyConfiguration"),
            new OA\Property(property: "calendar", ref: "#/components/schemas/CalendarConfiguration"),
            new OA\Property(property: "timeTracking", ref: "#/components/schemas/TimeTrackingConfiguration"),
            new OA\Property(property: "generativeContentPrompt", ref: "#/components/schemas/GenerativeContentPromptConfiguration"),
            new OA\Property(property: "highlight", ref: "#/components/schemas/HighlightConfiguration"),
            new OA\Property(property: "ephemeralTasks", ref: "#/components/schemas/EphemeralTasksConfiguration"),
            new OA\Property(property: "agent", ref: "#/components/schemas/AgentConfiguration")
        ]
    )]
    class AppConfiguration {
        // Intentionally empty.
    }
?>
