<?php    
    namespace Common\Service\Authentication;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "UserRole",
        type: "string",
        description: "An enum representing a user role"
    )]
    enum UserRole : string {
        case AirlineRead = "airline.read";
        case AirlineEdit = "airline.edit";
        case AirportRead = "airport.read";
        case AirportEdit = "airport.edit";
        case CategoryRead = "category.read";
        case CategoryEdit = "category.edit";
        case CategoryHighlightRead = "category.highlight.read";
        case CategoryHighlightEdit = "category.highlight.edit";
        case CategoryStatisticsRead = "category.statistics.read";
        case ConfigurationRead = "configuration.read";
        case ConfigurationEdit = "configuration.edit";
        case DeviceRead = "device.read";
        case DeviceEdit = "device.edit";
        case DocumentRead = "document.read";
        case DocumentEdit = "document.edit";
        case FitnessEdit = "fitness.edit";
        case FlightEdit = "flight.edit";
        case GeocodingRead = "geocoding.read";
        case GeocodingEdit = "geocoding.edit";
        case HighlightRead = "highlight.read";
        case HighlightEdit = "highlight.edit";
        case LabelRead = "label.read";
        case LabelEdit = "label.edit";
        case MonitoringRead = "monitoring.read";
        case PlaceRead = "place.read";
        case PlaceEdit = "place.edit";
        case PlaceCategoryRead = "place.category.read";
        case PlaceDateRead = "place.date.read";
        case PlaceNoteRead = "place.note.read";
        case PlaceNoteEdit = "place.note.edit";
        case PlaceHighlightRead = "place.highlight.read";
        case PlaceHighlightEdit = "place.highlight.edit";
        case PlaceLabelRead = "place.label.read";
        case PlaceLabelEdit = "place.label.edit";
        case PlaceAlbumRead = "place.album.read";
        case PlaceAlbumEdit = "place.album.edit";
        case RegionRead = "region.read";
        case RegionEdit = "region.edit";
        case StatisticsRead = "statistics.read";
        case SubscriptionRead = "subscription.read";
        case SubscriptionEdit = "subscription.edit";
        case TrackerRead = "tracker.read";
        case TrackerEdit = "tracker.edit";
        case TripRead = "trip.read";
        case TripEdit = "trip.edit";
        case TripExpenseRead = "trip.expense.read";
        case TripExpenseEdit = "trip.expense.edit";
        case TripNoteRead = "trip.note.read";
        case TripNoteEdit = "trip.note.edit";
        case TripHighlightRead = "trip.highlight.read";
        case TripHighlightEdit = "trip.highlight.edit";
        case TripStatisticsRead = "trip.statistics.read";
        case TripStayRead = "trip.stay.read";
        case TripFlightRead = "trip.flight.read";
        case TripFitnessRead = "trip.fitness.read";
        case TripPublicHolidayRead = "trip.holiday.read";
        case VoucherRead = "voucher.read";
        case VoucherEdit = "voucher.edit";
        case YearRead = "year.read";
        case YearEdit = "year.edit";
        case YearHighlightRead = "year.highlight.read";
        case YearHighlightEdit = "year.highlight.edit";
        case YearFitnessRead = "year.fitness.read";
        case YearStatisticsRead = "year.statistics.read";
        case EventEdit = "event.edit";
        case SearchRead = "search.read";
        case SearchEdit = "search.edit";
        case EventNewDataConsistencyIssueDetectedRead = "event.newdataconsistencyissuedetected.read";
        case PhotoReplacingTriggeredEventProcessingStartedRead = "event.processingstarted.photoreplacingtriggered.read";
        case PhotoReplacingTriggeredEventProcessingEndedRead = "event.processingended.photoreplacingtriggered.read";
        case PhotoReplacingTriggeredEventProcessingFailedRead = "event.processingfailed.photoreplacingtriggered.read";
        case PhotosUploadingCompletedEventProcessingStartedRead = "event.processingstarted.photosuploadingcompleted.read";
        case PhotosUploadingCompletedEventProcessingEndedRead = "event.processingended.photosuploadingcompleted.read";
        case PhotosUploadingCompletedEventProcessingFailedRead = "event.processingfailed.photosuploadingcompleted.read";
        case PhotosUploadingTriggeredEventProcessingStartedRead = "event.processingstarted.photosuploadingtriggered.read";
        case PhotosUploadingTriggeredEventProcessingEndedRead = "event.processingended.photosuploadingtriggered.read";
        case PhotosUploadingTriggeredEventProcessingFailedRead = "event.processingfailed.photosuploadingtriggered.read";
        case FolderSynchronizationRequestedEventProcessingStartedRead = "event.processingstarted.foldersynchronizationrequested.read";
        case FolderSynchronizationRequestedEventProcessingEndedRead = "event.processingended.foldersynchronizationrequested.read";
        case FolderSynchronizationRequestedEventProcessingFailedRead = "event.processingfailed.foldersynchronizationrequested.read";
        case AgentShutdownRequestedEventProcessingStartedRead = "event.processingstarted.agentshutdownrequested.read";
        case AgentShutdownRequestedEventProcessingEndedRead = "event.processingended.agentshutdownrequested.read";
        case AgentShutdownRequestedEventProcessingFailedRead = "event.processingfailed.agentshutdownrequested.read";
        case EventFitnessActivityDetectedRead = "event.fitnessactivitydetected.read";
        case EventDeviceLogOnRequestedRead = "event.devicelogonrequested.read";
        case EventFlightLoggedRead = "event.flightlogged.read";
        case IamAuthEdit = "iam.auth.edit";
        case PortalFutureRead = "portal.future.read";
        case PortalWarningRead = "portal.warning.read";
        case BridgeXLocationRead = "bridgex.location.read";

        public function implies(UserRole $role) : bool {
            if ($this === $role) {
                return true;
            }

            if (str_ends_with($this->value, ".edit")) {
                $baseResource = str_replace(".edit", "", $this->value);
                return $role->value === $baseResource . ".read";
            }

            return false;
        }
    }
?>