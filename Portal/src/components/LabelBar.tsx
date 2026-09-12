import { Plus, Trash2 } from "lucide-react"
import { toast } from "sonner"

import { useConfiguration } from "../contexts/ConfigContext.tsx"
import { useLabels } from "../hooks/useLabels.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Label } from "../types/CoreSwaggerTypes.ts"
import { getUnicode } from "../utils/formattingUtils.ts"
import AppLink from "./AppLink.tsx"
import Bar from "./Bar.tsx"
import BarItem from "./BarItem.tsx"

interface LabelBarProps {
    labels: Label[] | null
    onLabelAdded?: (label: string) => Promise<Label>
    onLabelRemoved?: (labelId: string) => Promise<void>
}

type DynamicLabel = { name: string }

export default function LabelBar({ labels, onLabelAdded, onLabelRemoved }: LabelBarProps) {
    const { showCreateLabelToast, showAssignLabelToast, showUnassignLabelToast } = usePredefinedUserInput()
    const { configuration } = useConfiguration()

    const allKnownLabels = useLabels()
    const unassignedLabels = allKnownLabels?.filter(label => !labels?.some(existingLabel => existingLabel.id === label.id)
        && !configuration?.dynamicLabels?.some((dynamicLabel: DynamicLabel) => dynamicLabel.name == label.name))

    const handleKnownLabelAdded = (label: Label) => {
        if (onLabelAdded) {
            onLabelAdded(label.name).then(label => toast.success(`Štítek ${label.name} byl úspěšně přiřazen.`)).catch(e => toast.error(`Štítek ${label.name} nebyl přiřazen.`))
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
                                {label.metadata?.unicode && (
                                    `${getUnicode(label.metadata.unicode)} `
                                )}
                                {label.name}
                            </AppLink>
                            <button
                                onClick={() => handleLabelRemoved(label)}
                                className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center btn-icon">
                                <Trash2 size={16} />
                            </button>
                        </BarItem>
                    ) : (
                        <BarItem
                            key={label.id}
                            to={label}>
                            {label.metadata?.unicode && (
                                `${getUnicode(label.metadata.unicode)} `
                            )}
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
                                {label.metadata?.unicode && (
                                    `${getUnicode(label.metadata.unicode)} `
                                )}
                                {label.name}
                            </AppLink>
                            <button
                                onClick={() => handleKnownLabelAdded(label)}
                                className="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center btn-icon">
                                <Plus size={16} />
                            </button>
                        </BarItem>
                    ))}
                    {onLabelAdded && (
                        <BarItem>
                            <button
                                onClick={handleUnknownLabelAdded}
                                className="btn-icon">
                                <Plus size={16} />
                            </button>
                        </BarItem>
                    )}
                </>
            )}
        </Bar>
    )
}