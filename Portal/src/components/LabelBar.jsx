import { Plus } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showInputToast from "./InputToast"

export default function LabelBar({ labels, onLabelAdded }) {
    const { isAdmin } = useAuth()

    if (labels.length === 0 && !isAdmin()) {
        return null
    }

    const handleLabelAdded = () => {
        showInputToast("Zadej jméno štítku:",
            name,
            "Štítek byl úspěšně přidán",
            "Nepodařilo se přidat štítek",
            onLabelAdded
        )
    }

    return (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {labels.map((label, index) => (
                <a
                    key={index}
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition"
                    href={`/label/${label.name}`}>
                    {label.name}
                </a>
            ))}
            {isAdmin() && (
                <button
                    onClick={handleLabelAdded}
                    className="rounded-full bg-white/80 backdrop-blur-sm text-black shadow-md hover:bg-white transition-colors px-3 py-1 text-sm font-medium flex items-center space-x-2">
                    <Plus size={16} />
                </button>
            )}
        </div>
    )
}