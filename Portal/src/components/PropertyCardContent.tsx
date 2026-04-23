import type React from "react"

interface PropertyCardContentProps {
    properties: Record<string, React.ReactNode | null>
}

export default function PropertyCardContent({ properties }: PropertyCardContentProps) {
    return properties && (
        <ul className="space-y-0.5 my-2">
            {Object.entries(properties).filter(([, value]) => value).map(([key, value]) => (
                <li
                    key={key}
                    className="text-gray-700">
                    <span className="font-semibold">
                        {key}:
                    </span>
                    {" "}
                    {value}
                </li>
            ))}
        </ul>
    )
}