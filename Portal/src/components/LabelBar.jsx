import { Plus, Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import clsx from "clsx"
import showConfirmToast from "./ConfirmToast"
import { useConfiguration } from "../contexts/ConfigContext"

export default function LabelBar({ labels, onLabelAdded, onLabelRemoved }) {
    const { isAdmin } = useAuth()
    const configuration = useConfiguration()

    if (labels.length === 0 && !isAdmin()) {
        return null
    }

    const handleLabelAdded = labelName => {
        showConfirmToast(`Opravdu chceš přidat štítek '${labelName}'?`,
            "Štítek byl úspěšně přidán",
            "Nepodařilo se přidat štítek",
            async () => onLabelAdded(labelName))
    }

    const handleLabelRemoved = label => {
        showConfirmToast(`Opravdu chceš odstranit štítek '${label.name}'?`,
            "Štítek byl úspěšně odstraněn",
            "Nepodařilo se odstranit štítek",
            async () => onLabelRemoved(label.id))
    }

    const assignedLabelNames = labels.map(label => label.name)
    const unassignedLabelNames = configuration ? Object.values(configuration.labels).flat().filter(label => !assignedLabelNames.includes(label)) : []

    return (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {labels.map((label, index) => (
                <div
                    key={index}
                    className="relative w-full lg:w-auto bg-white rounded-lg shadow px-4 py-2 flex items-center">
                    <a
                        href={`/label/${label.name}`}
                        className={clsx(
                            "text-sm font-medium text-center lg:text-left px-6 lg:pl-0 w-full",
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
            {onLabelAdded && isAdmin() && unassignedLabelNames.map((labelName, index) => (
                <div
                    key={index}
                    className="relative w-full lg:w-auto bg-white rounded-lg shadow px-4 py-2 flex items-center">
                    <span className="text-sm font-medium text-center lg:text-left px-6 lg:pl-0 w-full lg:pr-5">
                        {labelName}
                    </span>
                    <button
                        onClick={() => handleLabelAdded(labelName)}
                        className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full hover:bg-gray-200 transition-colors">
                        <Plus size={16} />
                    </button>
                </div>
            ))}
        </div>
    )
}