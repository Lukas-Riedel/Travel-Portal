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

    const trips = useRegularTrips({ include: ["flights"] })
    const { timeTrackingEvents, createTimeTrackingEvent, removeTimeTrackingEvent } = useTimeTrackingEvents(["overtime", "vacation", "selfcare", "tenure", "plannedWork"])
    const { isFreeDay } = usePublicHolidays(trips?.at(-1)?.year)

    return hasRole(UserRole.TrackerRead) && (
        <>
            <TrackerCalendar
                trips={trips}
                isFreeDay={isFreeDay}
                overtimeEvents={timeTrackingEvents["overtime"]}
                plannedWorkEvents={timeTrackingEvents["plannedWork"]}
                vacationEvents={timeTrackingEvents["vacation"]}
                selfcareEvents={timeTrackingEvents["selfcare"]}
                tenureEvents={timeTrackingEvents["tenure"]}
                onEventCreated={hasRole(UserRole.TrackerEdit) && createTimeTrackingEvent}
                onEventRemoved={hasRole(UserRole.TrackerEdit) && removeTimeTrackingEvent} />
            <TimeOffBalanceSummary
                overtimeEvents={timeTrackingEvents["overtime"]}
                vacationEvents={timeTrackingEvents["vacation"]}
                selfcareEvents={timeTrackingEvents["selfcare"]}
                tenureEvents={timeTrackingEvents["tenure"]} />
            {hasRole(UserRole.UiFutureRead) && (
                <TripTable
                    trips={trips?.filter(trip => trip?.isFuture())}
                    isFreeDay={isFreeDay}
                    overtimeEvents={timeTrackingEvents["overtime"]}
                    plannedWorkEvents={timeTrackingEvents["plannedWork"]}
                    vacationEvents={timeTrackingEvents["vacation"]}
                    selfcareEvents={timeTrackingEvents["selfcare"]}
                    tenureEvents={timeTrackingEvents["tenure"]} />
            )}
        </>
    )
}