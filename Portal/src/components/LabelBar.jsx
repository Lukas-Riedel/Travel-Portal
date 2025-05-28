import { Plus, Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showInputToast from "./InputToast"
import clsx from "clsx"
import showConfirmToast from "./ConfirmToast"

export default function LabelBar({ labels, onLabelAdded, onLabelRemoved }) {
    const { isAdmin } = useAuth()

    if (labels.length === 0 && !isAdmin()) {
        return null
    }

    const handleLabelAdded = () => {
        showInputToast("Zadej jméno štítku:",
            "",
            "Štítek byl úspěšně přidán",
            "Nepodařilo se přidat štítek",
            onLabelAdded
        )
    }

    const handleLabelRemoved = label => {
        showConfirmToast(`Opravdu chceš odstranit štítek '${label.name}'?`,
            "Štítek byl úspěšně odstraněn",
            "Nepodařilo se odstranit štítek",
            async () => onLabelRemoved(label.id))
    }

    return (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {labels.map((label, index) => (
                <div key={index} className="relative w-full lg:w-auto bg-white rounded-lg shadow px-4 py-2">
                    <a
                        href={`/label/${label.name}`}
                        className={clsx(
                            "block text-sm font-medium text-center lg:text-left px-6 lg:pl-0 w-full",
                            isAdmin() ? "lg:pr-5" : "lg:pr-0"
                        )}>
                        {label.name}
                    </a>
                    {onLabelRemoved && isAdmin() && (
                        <button
                            onClick={() => handleLabelRemoved(label)}
                            className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full hover:bg-gray-200 transition-colors">
                            <Trash2 size={16} />
                        </button>
                    )}
                </div>
            ))}
            {onLabelAdded && isAdmin() && (
                <button
                    onClick={handleLabelAdded}
                    className="rounded-full bg-white/80 backdrop-blur-sm text-black justify-center shadow-md hover:bg-white transition-colors px-3 py-1 text-sm font-medium flex items-center space-x-2">
                    <Plus size={16} />
                </button>
            )}
        </div>
    )
}