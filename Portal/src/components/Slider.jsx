import { useEffect, useState } from "react"

export default function Slider({ name, valueFormatter, value, defaultValue, minValue, maxValue, step, onValueChanged }) {
    const [innerValue, setInnerValue] = useState(value ?? maxValue)

    useEffect(() => {
        setInnerValue(value)
    }, [value])

    const handleChange = event => {
        const newValue = Math.max(minValue, Math.min(maxValue, Number(event.target.value)))
        setInnerValue(newValue)
        onValueChanged(newValue)
    }

    const handleDoubleClick = () => {
        if (defaultValue !== undefined) {
            setInnerValue(defaultValue)
            onValueChanged(defaultValue)
        }
    }

    return name && onValueChanged && (
        <div className="flex flex-col items-center space-y-1 m-2">
            <label className="text-xl font-semibold">
                {name}
            </label>
            {valueFormatter ? (
                <label className="text-l text-gray-600">
                    {valueFormatter ? valueFormatter(innerValue) : innerValue}
                </label>
            ) : (
                <input
                    type="number"
                    step={step}
                    min={minValue}
                    max={maxValue}
                    value={innerValue}
                    onChange={handleChange}
                    className="w-full text-center text-sm font-semibold bg-transparent text-l text-gray-600 rounded border border-white/20 py-0.5 focus:outline-none focus:ring-1 focus:ring-white/50 focus:border-white/50 transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
            )}
            <div className="my-2 w-full">
                <input
                    type="range"
                    step={step}
                    min={minValue}
                    max={maxValue}
                    value={innerValue}
                    onChange={handleChange}
                    onDoubleClick={handleDoubleClick}
                    className="my-2 w-full h-1 accent-blue-700" />
            </div>
        </div>
    )
}
