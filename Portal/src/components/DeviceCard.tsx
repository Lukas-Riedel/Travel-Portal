import { useMemo } from "react"
import { FolderSync } from "lucide-react"
import { useTranslation } from "react-i18next"
import LoadingCard from "./LoadingCard.tsx"
import { formatDeviceType } from "../utils/formattingUtils.ts"
import type { Device } from "../types/CoreSwaggerTypes.ts"
import { isDeviceOnline } from "../utils/deviceUtils.ts"
import { formatTimestamp } from "../utils/timeUtils.ts"
import PropertyCardContent from "./PropertyCardContent.tsx"
import Card from "./Card.tsx"

interface SynchronizedFolder {
    path: string
    expiration: number
}

interface DeviceCardProps {
    device: Device | null
    onFolderSynchronizationRequested?: (deviceId: string) => void
}

export default function DeviceCard({ device, onFolderSynchronizationRequested }: DeviceCardProps) {
    const { t } = useTranslation()

    const isOnline = useMemo(() => device && isDeviceOnline(device), [device])

    const handleFolderSynchronizedRequested = () => {
        if (onFolderSynchronizationRequested) {
            onFolderSynchronizationRequested(device.id)
        }
    }

    const properties = useMemo(() => device && ({
        [t("device.label.type")]: formatDeviceType(device.type),
        [t("device.label.status")]: (
            <span className={isOnline ? "text-green-600" : "text-red-600"}>
                {isOnline ? t("device.label.online") : t("device.label.offline")}
            </span>
        ),
        [t("device.label.lastSeen")]: !isOnline && formatTimestamp(device.lastSeen, t("general.format.datetime.year.excluded")),
        [t("device.label.synchronizedFolders")]: device.data?.synchronizedFolders?.length && (
            <ul className="list-disc list-inside">
                {device.data.synchronizedFolders.map((folder: SynchronizedFolder) => (
                    <li className="break-all whitespace-normal font-mono">
                        {formatTimestamp(folder.expiration, t("general.format.datetime.year.included"))} - {decodeURIComponent(folder.path)}
                    </li>))}
            </ul>
        )
    }), [device, t])

    if (!device) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card className="relative">
            <div className="text-lg font-semibold">
                {device.name}
            </div>
            <PropertyCardContent properties={properties} />
            {onFolderSynchronizationRequested && isOnline && (
                <button
                    onClick={handleFolderSynchronizedRequested}
                    className="absolute bottom-2 right-2 p-1 rounded text-green-600 hover:bg-gray-100 transition-colors">
                    <FolderSync size={16} />
                </button>
            )}
        </Card>
    )
}