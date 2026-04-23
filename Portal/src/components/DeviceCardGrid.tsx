import type { Device } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import DeviceCard from "./DeviceCard.tsx"

interface DeviceCardGridProps {
    devices: Device[] | null
    rowSize: number
    onFolderSynchronizationRequested?: (deviceId: string) => void
}

export default function DeviceCardGrid({ devices, rowSize, onFolderSynchronizationRequested }: DeviceCardGridProps) {
    return (
        <CardGrid rowSize={rowSize}>
            {devices?.map(device => (
                <DeviceCard
                    key={device.id}
                    device={device}
                    onFolderSynchronizationRequested={onFolderSynchronizationRequested} />
            ))}
        </CardGrid>
    )
}