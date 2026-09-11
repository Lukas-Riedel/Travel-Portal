import TimeOffBalanceSummary from "../components/TimeOffBalanceSummary"
import TrackerCalendar from "../components/TrackerCalendar"
import TripTable from "../components/TripTable"
import { useAuth } from "../contexts/AuthContext"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { useTimeTrackingEvents } from "../hooks/useTimeTrackingEvents"
import { TimeTrackingEventType, TripIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import { isFutureTrip } from "../utils/tripUtils.ts"

export default function TrackerPage() {
    const { hasRole } = useAuth()

    const { trips } = useRegularTrips({ include: [TripIncludedEntity.Flights] })
    const { timeTrackingEvents, createTimeTrackingEvent, removeTimeTrackingEvent } = useTimeTrackingEvents([TimeTrackingEventType.Overtime, TimeTrackingEventType.Vacation, TimeTrackingEventType.Selfcare, TimeTrackingEventType.Tenure, TimeTrackingEventType.PlannedWork])

    return hasRole(UserRole.TrackerRead) && (
        <>
            <TrackerCalendar
                trips={trips}
                timeTrackingEvents={timeTrackingEvents}
                onEventCreated={hasRole(UserRole.TrackerEdit) ? createTimeTrackingEvent : undefined}
                onEventRemoved={hasRole(UserRole.TrackerEdit) ? removeTimeTrackingEvent : undefined} />
            <TimeOffBalanceSummary timeTrackingEvents={timeTrackingEvents} />
            {hasRole(UserRole.PortalFutureRead) && (
                <TripTable
                    trips={trips?.filter(trip => trip && isFutureTrip(trip)) ?? null}
                    timeTrackingEvents={timeTrackingEvents} />
            )}
        </>
    )
}
