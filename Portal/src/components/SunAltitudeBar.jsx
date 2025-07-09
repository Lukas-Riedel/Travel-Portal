import SunAltitudeIcon from "./SunAltitudeIcon"

export default function SunAltitudeBar({ place }) {
    return (
        <div className="grid grid-cols-3 sm:grid-cols-6 gap-4 my-6 text-center">
            {[+0, +20, +30, -30, -20, -0].map(altitude => (
                <SunAltitudeIcon
                    key={altitude}
                    place={place}
                    altitude={altitude} />
            ))}
        </div>
    )
}