import TimeOffBalanceSummary from "../components/TimeOffBalanceSummary";
import TrackerCalendar from "../components/TrackerCalendar";
import TripTable from "../components/TripTable";
import { useAuth } from "../contexts/AuthContext";
import { usePublicHolidays } from "../hooks/usePublicHolidays";
import { useRegularTrips } from "../hooks/useRegularTrips";
import { useTimeTrackingEvents } from "../hooks/useTimeTrackingEvents";
import { UserRole } from "../types/CoreSwaggerTypes.ts";

export default function TrackerPage() {
    const { hasRole } = useAuth()

    const { trips } = useRegularTrips({ include: ["flights"] })
    const { timeTrackingEvents, createTimeTrackingEvent, removeTimeTrackingEvent } = useTimeTrackingEvents(["overtime", "vacation", "selfcare", "tenure", "plannedWork"])

    return hasRole(UserRole.TrackerRead) && (
        <>
            <TrackerCalendar
                trips={trips}
                timeTrackingEvents={timeTrackingEvents}
                onEventCreated={hasRole(UserRole.TrackerEdit) && createTimeTrackingEvent}
                onEventRemoved={hasRole(UserRole.TrackerEdit) && removeTimeTrackingEvent} />
            <TimeOffBalanceSummary timeTrackingEvents={timeTrackingEvents} />
            {hasRole(UserRole.PortalFutureRead) && (
                <TripTable
                    trips={trips?.filter(trip => trip?.isFuture())}
                    timeTrackingEvents={timeTrackingEvents} />
            )}
        </>
    )
}