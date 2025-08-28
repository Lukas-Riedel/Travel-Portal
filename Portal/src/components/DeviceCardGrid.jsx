import CardGrid from "./CardGrid";
import DeviceCard from "./DeviceCard";

export default function DeviceCardGrid({ devices }) {
    return (
        <CardGrid cardsPerRowCount={4}>
            {devices?.map(device => (
                <DeviceCard
                    key={device.token}
                    device={device} />
            ))}
        </CardGrid>
    )
}