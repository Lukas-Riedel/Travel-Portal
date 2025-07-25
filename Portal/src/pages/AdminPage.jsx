import { useState } from "react"
import { useAuth } from "../contexts/AuthContext"
import TabMenu from "../components/TabMenu"
import TripSummary from "../components/TripSummary"
import NoteBar from "../components/NoteBar"
import { useUpcomingOrCurrentTrip } from "../hooks/useUpcomingOrCurrentTrip"
import ExpenseSummary from "../components/ExpenseSummary"
import { Plus } from "lucide-react"
import showFormToast from "../components/FormToast"
import FloatingButton from "../components/FloatingButton"
import { useApi } from "../hooks/useApi"
import { toZonedTime } from "date-fns-tz"

const labels = ["Aktuální výlet", "Správa letů"]

export default function AdminPage() {
    const { isAdmin } = useAuth()
    const { createScheduledFlight, createWatchedFlight, getCoordinates } = useApi()

    const { trip: upcomingOrCurrentTrip, createTripNote, removeTripNote, createTripExpense,
        updateTripExpenseDescription, updateTripExpenseValue, removeTripExpense } = useUpcomingOrCurrentTrip()

    const [activeTab, setActiveTab] = useState(0)

    const getAirportTimezone = async (airportName) => (await getCoordinates("Letiště " + airportName))?.timezone
    const getAirportLocalTime = async (airportName, time) => Math.round(toZonedTime(time, await getAirportTimezone(airportName))?.getTime() / 1000)

    const handleFlightCreated = () => {
        showFormToast(
            "Zadej údaje o letu k přidání:",
            [
                { label: "Číslo letu", required: true, placeholder: "EK139" },
                { label: "Místo odletu", required: true, placeholder: "Praha" },
                { label: "Čas odletu (v časové zóně místa odletu)", required: true, type: "datetime-local" },
                { label: "Místo příletu", required: true, placeholder: "Dubaj" },
                { label: "Čas příletu (v časové zóně místa příletu)", required: true, type: "datetime-local" },
                {
                    label: "Typ", required: true, type: "select", options: [
                        { id: "scheduled", name: "Potvrzený" },
                        { id: "watched", name: "Sledovaný" }
                    ]
                }
            ],
            "Let byl úspěšně přidán",
            "Při přidávání letu došlo k chybě",
            async (flight, from, scheduledDeparture, to, scheduledArrival, type) => {
                if (type === "scheduled") {
                    return createScheduledFlight(flight, from, to, await getAirportLocalTime(from, scheduledDeparture), await getAirportLocalTime(to, scheduledArrival))
                }
                else if (type === "watched") {
                    return createWatchedFlight(flight, from, to, await getAirportLocalTime(from, scheduledDeparture), await getAirportLocalTime(to, scheduledArrival))
                }
                else {
                    return Promise.reject(`Unknown flight type '${type}'.`)
                }
            }
        )
    }

    return isAdmin && (
        <>
            <TabMenu
                labels={labels}
                activeTab={activeTab}
                setActiveTab={setActiveTab} />
            {activeTab === 0 && (
                <>
                    <TripSummary tripId={upcomingOrCurrentTrip?.id} />
                    <NoteBar
                        notes={upcomingOrCurrentTrip?.notes}
                        onNoteCreated={createTripNote}
                        onNoteRemoved={removeTripNote} />
                    <ExpenseSummary
                        expenses={upcomingOrCurrentTrip?.expenses}
                        onExpenseCreated={createTripExpense}
                        onExpenseDescriptionUpdated={updateTripExpenseDescription}
                        onExpenseValueUpdated={updateTripExpenseValue}
                        onExpenseRemoved={removeTripExpense} />
                </>
            )}
            {activeTab === 1 && (
                <>
                    <FloatingButton
                        icon={Plus}
                        onClick={handleFlightCreated} />
                </>
            )}
        </>
    )
}