import LoadingCard from "./LoadingCard"
import { getDateTimeString } from "../utils/helpers"
import { useMemo } from "react"
import { useAuth } from "../contexts/AuthContext"
import { FolderSync } from "lucide-react"

const onlineStatusThresholdSeconds = 60

export default function DeviceCard({ device, onFolderSynchronizationRequested }) {
    const { isAdmin } = useAuth()

    const isOnline = useMemo(() => device.lastSeen + onlineStatusThresholdSeconds > Date.now() / 1000, [device])

    return device ? (
        <div className="relative bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full">
            <div className="text-lg font-semibold">
                {device.name}
            </div>
            <div className="my-2">
                <ul className="space-y-0.5">
                    <li className="text-gray-700 truncate">
                        <span className="font-semibold">Typ:</span> {device.type.toLowerCase().replace(/^./, c => c.toUpperCase())}
                    </li>
                    <li className="text-gray-700 truncate">
                        <span className="font-semibold">Stav:</span> {isOnline ? (
                            <span className="text-green-600">Online</span>
                        ) : (
                            <span className="text-red-600">Offline</span>
                        )}
                    </li>
                    {device.data?.synchronizedFolders && (
                        <li>
                            <span className="font-semibold">Synchronizované složky:</span>
                            <ul className="list-disc list-inside">
                                {device.data.synchronizedFolders.map(folder => (
                                    <li className="break-all whitespace-normal font-mono">
                                        {getDateTimeString(folder.expiration)} - {decodeURIComponent(folder.path)}
                                    </li>
                                ))}
                            </ul>
                        </li>
                    )}
                    {!isOnline && (
                        <li>
                            <span className="font-semibold">Naposledy spatřeno:</span> {getDateTimeString(device.lastSeen, false)}
                        </li>
                    )}
                </ul>
            </div>
            {onFolderSynchronizationRequested && isOnline && isAdmin && (
                <button
                    onClick={() => onFolderSynchronizationRequested(device.id)}
                    className="absolute bottom-2 right-2 p-1 rounded text-green-600 hover:bg-gray-100 transition-colors">
                    <FolderSync size={16} />
                </button>
            )}
        </div>
    ) : (
        <LoadingCard />
    )
}