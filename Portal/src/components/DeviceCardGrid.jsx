import CardGrid from "./CardGrid.tsx";
import DeviceCard from "./DeviceCard";

export default function DeviceCardGrid({ devices, onFolderSynchronizationRequested }) {
    return (
        <CardGrid rowSize={4}>
            {devices?.map(device => (
                <DeviceCard
                    key={device.id}
                    device={device}
                    onFolderSynchronizationRequested={onFolderSynchronizationRequested} />
            ))}
        </CardGrid>
    )
}