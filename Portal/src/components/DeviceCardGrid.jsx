import CardGrid from "./CardGrid";
import DeviceCard from "./DeviceCard";

export default function DeviceCardGrid({ devices, onFolderSynchronizationRequested }) {
    return (
        <CardGrid cardsPerRowCount={4}>
            {devices?.map(device => (
                <DeviceCard
                    key={device.id}
                    device={device}
                    onFolderSynchronizationRequested={onFolderSynchronizationRequested} />
            ))}
        </CardGrid>
    )
}