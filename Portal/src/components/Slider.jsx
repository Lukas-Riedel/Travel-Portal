import { useEffect, useState } from "react"

export default function Slider({ name, valueFormatter, value, minValue, maxValue, onValueChanged }) {
    const [innerValue, setInnerValue] = useState(value ?? maxValue)

    useEffect(() => {
        setInnerValue(value)
    }, [value])

    const handleChange = event => {
        const newValue = Number(event.target.value)
        setInnerValue(newValue)
        onValueChanged(newValue)
    }

    return name && valueFormatter && onValueChanged && (
        <div className="flex flex-col items-center space-y-1 my-4">
            <label className="text-xl font-semibold">
                {name}
            </label>
            <label className="text-l text-gray-600">
                {valueFormatter(innerValue)}
            </label>
            <div className="my-2 w-full">
                <input
                    type="range"
                    min={minValue}
                    max={maxValue}
                    value={innerValue}
                    onChange={handleChange}
                    className="my-2 w-full h-1 accent-blue-700" />
            </div>
        </div>
    )
}
