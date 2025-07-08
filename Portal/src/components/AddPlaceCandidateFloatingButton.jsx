import { Plus } from "lucide-react"
import FloatingButton from "./FloatingButton"
import showFormToast from "./FormToast"
import { useAuth } from "../contexts/AuthContext"

export default function AddPlaceCandidateFloatingButton({ onCandidatePlaceCreated }) {
    const { isAdmin } = useAuth()

    const handleCandidatePlaceCreated = () => {
        showFormToast(
            "Zadej údaje o místě k přidání:",
            [
                { label: "Jméno", required: true },
                { label: "Adresa", required: true }
            ],
            "Místo bylo úspěšně přidáno",
            "Při přidávání místa došlo k chybě",
            async (name, address) => onCandidatePlaceCreated(name, address)
        )
    }

    return isAdmin && onCandidatePlaceCreated && (
        <FloatingButton
            icon={Plus}
            onClick={handleCandidatePlaceCreated} />
    )
}