export enum EventType {
    PhotosUploadingTriggered = "PhotosUploadingTriggered",
    PhotoReplacingTriggered = "PhotoReplacingTriggered",
    AllAlbumsInvalidated = "AllAlbumsInvalidated",
    FolderSynchronizationRequested = "FolderSynchronizationRequested",
    ProcessingStarted = "ProcessingStarted",
    ProcessingEnded = "ProcessingEnded",
    ProcessingFailed = "ProcessingFailed",
    NewDataConsistencyIssuesDetected = "NewDataConsistencyIssuesDetected",
    TaskDeadlineReached = "TaskDeadlineReached",
    FlightLogged = "FlightLogged",
    FlightReminderReceived = "FlightReminderReceived"
}