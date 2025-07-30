import { Plus, Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import clsx from "clsx"
import showConfirmToast from "./ConfirmToast"
import { Link } from "react-router-dom"
import { useMemo } from "react"
import { TailSpin } from "react-loader-spinner"
import { useLabels } from "../hooks/useLabels"
import showInputToast from "./InputToast"
import { useConfiguration } from "../contexts/ConfigContext"

const loadingLabelsCount = 3

export default function LabelBar({ labels, onLabelAdded, onLabelRemoved }) {
    const { isAdmin } = useAuth()

    const configuration = useConfiguration();

    const allLabels = useLabels()

    const unassignedLabels = useMemo(() => allLabels?.filter(label => !labels?.some(existingLabel => existingLabel.id === label.id) && !configuration?.dynamicLabels?.[label.name], [allLabels, configuration, labels]))

    const handleKnownLabelAdded = labelName => {
        showConfirmToast(`Opravdu chceš přidat štítek '${labelName}'?`,
            "Štítek byl úspěšně přidán",
            "Nepodařilo se přidat štítek",
            async () => onLabelAdded(labelName))
    }
    const handleUnknownLabelAdded = () => {
        showInputToast("Zadej jméno štítku k přidání:",
            "",
            "Štítek byl úspěšně přidán",
            "Nepodařilo se přidat štítek",
            async (labelName) => onLabelAdded(labelName))
    }

    const handleLabelRemoved = label => {
        showConfirmToast(`Opravdu chceš odstranit štítek '${label.name}'?`,
            "Štítek byl úspěšně odstraněn",
            "Nepodařilo se odstranit štítek",
            async () => onLabelRemoved(label.id))
    }

    return (!labels || labels.length > 0 || isAdmin) && (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {labels ? (
                <>
                    {labels.map(label => (
                        <div
                            key={label.id}
                            className="relative w-full lg:w-auto bg-white rounded-lg shadow px-4 py-2 flex items-center hover:bg-gray-100 transition">
                            <Link
                                to={`${window.location.pathname.startsWith("/plan") ? "/plan" : ""}/label/${label.id}`}
                                className={clsx(
                                    "text-sm font-medium text-center lg:text-left px-6 lg:pl-0 w-full",
                                    isAdmin ? "lg:pr-5" : "lg:pr-0"
                                )}>
                                {label.name}
                            </Link>
                            {onLabelRemoved && isAdmin && !configuration?.dynamicLabels?.[label.name] && (
                                <button
                                    onClick={() => handleLabelRemoved(label)}
                                    className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center btn-icon-hover">
                                    <Trash2 size={16} />
                                </button>
                            )}
                        </div>
                    ))}
                    {onLabelAdded && isAdmin && unassignedLabels?.map(label => (
                        <div
                            key={label.id}
                            className="relative w-full lg:w-auto bg-white rounded-lg shadow px-4 py-2 flex items-center hover:bg-gray-100 transition">
                            <Link
                                to={`${window.location.pathname.startsWith("/plan") ? "/plan" : ""}/label/${label.id}`}
                                className="text-sm font-medium text-center lg:text-left px-6 lg:pl-0 w-full lg:pr-5">
                                {label.name}
                            </Link>
                            <button
                                onClick={() => handleKnownLabelAdded(label.name)}
                                className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center btn-icon-hover">
                                <Plus size={16} />
                            </button>
                        </div>
                    ))}
                    {onLabelAdded && isAdmin && (
                        <button
                            onClick={handleUnknownLabelAdded}
                            className="relative bg-white shadow px-4 py-2 flex items-center justify-center rounded-lg hover:bg-gray-100 transition">
                            <Plus size={16} />
                        </button>
                    )}
        </>
    ) : Array.from({ length: loadingLabelsCount }).map((_, index) => (
        <div
            key={index}
            className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition">
            <div className="mx-4 min-w-[36px] min-h-[24px] flex items-center justify-center">
                <TailSpin
                    color="black"
                    height={16}
                    width={16} />
            </div>
        </div>
    ))
}
        </div >
    )
}