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
import Bar from "./Bar.tsx"
import BarItem from "./BarItem.tsx"

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
        if (onLabelAdded) {
            showAssignLabelToast(() => onLabelAdded(label.name))
        }
    }

    const handleUnknownLabelAdded = () => {
        if (onLabelAdded) {
            showCreateLabelToast(onLabelAdded)
        }
    }

    const handleLabelRemoved = (label: Label) => {
        if (onLabelRemoved) {
            showUnassignLabelToast(() => onLabelRemoved(label.id))
        }
    }

    return (!labels || labels.length > 0 || onLabelAdded) && (
        <Bar>
            {labels && (
                <>
                    {labels.map(label => onLabelRemoved && !configuration?.dynamicLabels?.some((dynamicLabel: DynamicLabel) => dynamicLabel.name == label.name) ? (
                        <BarItem
                            key={label.id}
                            className="relative">
                            <AppLink
                                to={label}
                                className="text-sm font-medium text-center lg:text-left px-6 lg:pl-0 w-full lg:pr-5">
                                {label.name}
                            </AppLink>
                            <button
                                onClick={() => handleLabelRemoved(label)}
                                className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center btn-icon-hover">
                                <Trash2 size={16} />
                            </button>
                        </BarItem>
                    ) : (
                        <BarItem
                            key={label.id}
                            to={label}>
                            {label.name}
                        </BarItem>
                    ))}
                    {onLabelAdded && unassignedLabels?.map(label => (
                        <BarItem
                            key={label.id}
                            className="relative">
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
                        </BarItem>
                    ))}
                    {onLabelAdded && (
                        <BarItem>
                            <button
                                onClick={handleUnknownLabelAdded}
                                className="btn-icon-hover">
                                <Plus size={16} />
                            </button>
                        </BarItem>
                    )}
                </>
            )}
        </Bar>
    )
}