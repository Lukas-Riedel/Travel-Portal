import SunAltitudeIcon from "./SunAltitudeIcon"

export default function SunAltitudeBar({ place }) {
    return place && (
        <div className="grid grid-cols-3 sm:grid-cols-6 gap-4 my-6 text-center">
            {[+0, +20, +30, -30, -20, -0].map((altitude, index) => (
                <SunAltitudeIcon
                    key={index}
                    place={place}
                    altitude={altitude} />
            ))}
        </div>
    )
}