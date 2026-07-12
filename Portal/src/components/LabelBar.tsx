import { Plus, Trash2 } from "lucide-react"
import clsx from "clsx"
import { Link } from "react-router-dom"
import { useMemo } from "react"
import { TailSpin } from "react-loader-spinner"
import { useLabels } from "../hooks/useLabels.ts"
import { useConfiguration } from "../contexts/ConfigContext.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Label } from "../types/CoreSwaggerTypes.ts"
import AppLink from "./AppLink.tsx"

const LOADING_LABELS_COUNT = 3

interface LabelBarProps {
    labels: Label[] | null
    onLabelAdded: (label: string) => Promise<Label>
    onLabelRemoved: (labelId: string) => Promise<void>
}

type DynamicLabel = { name: string }

export default function LabelBar({ labels, onLabelAdded, onLabelRemoved }: LabelBarProps) {
    const { showCreateLabelToast, showAssignLabelToast, showUnassignLabelToast } = usePredefinedUserInput()
    const { configuration } = useConfiguration()

    const allKnownLabels = useLabels()
    const unassignedLabels = useMemo(() => allKnownLabels?.filter(label => !labels?.some(existingLabel => existingLabel.id === label.id)
        && !configuration?.dynamicLabels?.some((dynamicLabel: DynamicLabel) => dynamicLabel.name == label.name)),
        [allKnownLabels, configuration, labels])

    const handleKnownLabelAdded = (label: Label) => {
        showAssignLabelToast(() => onLabelAdded(label.name))
    }

    const handleUnknownLabelAdded = () => {
        showCreateLabelToast(onLabelAdded)
    }

    const handleLabelRemoved = (label: Label) => {
        showUnassignLabelToast(() => onLabelRemoved(label.id))
    }

    return (!labels || labels.length > 0 || onLabelAdded) && (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {labels ? (
                <>
                    {labels.map(label => (
                        <div
                            key={label.id}
                            className="relative w-full lg:w-auto bg-white rounded-lg shadow px-4 py-2 flex items-center hover:bg-gray-100 transition">
                            <AppLink
                                to={label}
                                className={clsx(
                                    "text-sm font-medium text-center lg:text-left px-6 lg:pl-0 w-full",
                                    onLabelAdded ? "lg:pr-5" : "lg:pr-0"
                                )}>
                                {label.name}
                            </AppLink>
                            {onLabelRemoved && !configuration?.dynamicLabels?.some((dynamicLabel: DynamicLabel) => dynamicLabel.name == label.name) && (
                                <button
                                    onClick={() => handleLabelRemoved(label)}
                                    className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center btn-icon-hover">
                                    <Trash2 size={16} />
                                </button>
                            )}
                        </div>
                    ))}
                    {onLabelAdded && unassignedLabels?.map(label => (
                        <div
                            key={label.id}
                            className="relative w-full lg:w-auto bg-white rounded-lg shadow px-4 py-2 flex items-center hover:bg-gray-100 transition">
                            <AppLink
                                to={label}
                                className="text-sm font-medium text-center lg:text-left px-6 lg:pl-0 w-full lg:pr-5">
                                {label.name}
                            </AppLink>
                            <button
                                onClick={() => handleKnownLabelAdded(label)}
                                className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center btn-icon-hover">
                                <Plus size={16} />
                            </button>
                        </div>
                    ))}
                    {onLabelAdded && (
                        <button
                            onClick={handleUnknownLabelAdded}
                            className="relative bg-white shadow px-4 py-2 flex items-center justify-center rounded-lg hover:bg-gray-100 transition">
                            <Plus size={16} />
                        </button>
                    )}
                </>
            ) : Array.from({ length: LOADING_LABELS_COUNT }).map((_, index) => (
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