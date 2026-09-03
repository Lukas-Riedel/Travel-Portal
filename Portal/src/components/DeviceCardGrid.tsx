import type { Device } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import DeviceCard from "./DeviceCard.tsx"

interface DeviceCardGridProps {
    devices: Device[] | null
    rowSize: number
    columnSize?: number
    onFolderSynchronizationRequested?: (deviceId: string) => void
}

export default function DeviceCardGrid({ devices, rowSize, columnSize, onFolderSynchronizationRequested }: DeviceCardGridProps) {
    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
            {devices?.map(device => (
                <DeviceCard
                    key={device.id}
                    device={device}
                    onFolderSynchronizationRequested={onFolderSynchronizationRequested} />
            ))}
        </CardGrid>
    )
}