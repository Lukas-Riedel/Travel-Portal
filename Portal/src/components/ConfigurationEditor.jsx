import { useState } from "react"
import ReactJson from "react-json-view"
import { TailSpin } from "react-loader-spinner"
import showConfirmToast from "./ConfirmToast"

export default function ConfigurationEditor({ configuration, onConfigurationUpdated }) {
    const [selectedKey, setSelectedKey] = useState(null)

    const formatConfigurationKeyName = key => key.replace(/([A-Z])/g, " $1").replace(/^./, s => s.toUpperCase()).trim()

    const handleConfigurationUpdated = value => {
        showConfirmToast("Opravdu chceš modifikovat tento konfigurační záznam?",
            "Konfigurační záznam byl uspěšně modifikován",
            "Nepodařilo se modifikovat konfigurační záznam",
            async () => onConfigurationUpdated(selectedKey, value)
        )
        
    }

    return configuration ? (
        <div className="flex h-[600px] border rounded-xl overflow-hidden">
            <div className="w-1/3 border-r p-3 overflow-y-auto bg-gray-100">
                {Object.keys(configuration).map(key => (
                    <button
                        key={key}
                        onClick={() => setSelectedKey(key)}
                        className={`block w-full text-left px-3 py-2 rounded hover:bg-gray-200 ${key === selectedKey ? "bg-gray-300 font-semibold" : ""}`}>
                        {formatConfigurationKeyName(key)}
                    </button>
                ))}
            </div>
            <div className="flex-1 p-3 overflow-auto">
                {selectedKey && (
                    <ReactJson
                        name={false}
                        src={{ [selectedKey]: configuration[selectedKey] }}
                        onEdit={edit => {
                            const newConfig = { ...configuration }
                            newConfig[selectedKey] = (typeof edit.updated_src[selectedKey] === "string" && !isNaN(edit.updated_src[selectedKey])
                                && edit.updated_src[selectedKey].trim() !== "") ? Number(edit.updated_src[selectedKey]) : edit.updated_src[selectedKey]
                            handleConfigurationUpdated(newConfig[selectedKey])
                            return true
                        }}
                        enableClipboard={false}
                        displayDataTypes={false}
                        displayObjectSize={false}
                        quotesOnKeys={false}
                        displayArrayKey={false}
                        indentWidth={4}
                    />
                )}
                {!selectedKey && (
                    <div className="flex items-center justify-center text-gray-500 h-full w-full">
                        Vyber klíč vlevo pro úpravu
                    </div>
                )}
            </div>
        </div>
    ) : (
        <div className="flex justify-center items-center min-h-[400px]">
            <TailSpin
                color="black"
                height={80}
                width={80} />
        </div>
    )
}
