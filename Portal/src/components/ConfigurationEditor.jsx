import { useState } from "react"
import ReactJson from "react-json-view"
import { TailSpin } from "react-loader-spinner"
import { useUserInput } from "../hooks/useUserInput.ts"
import { useAuth } from "../contexts/AuthContext"

export default function ConfigurationEditor({ configuration, onConfigurationUpdated }) {
    const { accessToken } = useAuth()
    const [selectedKey, setSelectedKey] = useState(null)
    const { showConfirmToast } = useUserInput()

    const formatConfigurationKeyName = key => key.replace(/([A-Z])/g, " $1").replace(/^./, s => s.toUpperCase()).trim()

    const handleConfigurationUpdated = value => {
        showConfirmToast(
            "Opravdu chceš modifikovat tento konfigurační záznam?",
            async () => onConfigurationUpdated(selectedKey, value),
            "Konfigurační záznam byl uspěšně modifikován",
            "Nepodařilo se modifikovat konfigurační záznam"
        )
    }

    return configuration ? (
        <div className="flex h-[600px] border rounded-xl overflow-hidden">
            <div className="w-1/4 border-r p-3 overflow-y-auto bg-gray-100">
                {Object.keys(configuration).map(key => (
                    <button
                        key={key}
                        onClick={() => setSelectedKey(key)}
                        className={`block w-full text-left px-3 py-2 rounded hover:bg-gray-200 ${key === selectedKey ? "bg-gray-300 font-semibold" : ""}`}>
                        {formatConfigurationKeyName(key)}
                    </button>
                ))}
                <form
                    action={import.meta.env.VITE_IAM_BASE_URL + "/google/auth"}
                    method="post"
                    target="_blank"
                    className="block w-full text-left px-3 py-2 rounded hover:bg-gray-200">
                    <input
                        type="hidden"
                        name="token"
                        value={accessToken} />
                    <button
                        type="submit"
                        className="w-full text-left bg-transparent border-none p-0 m-0">
                        Google APIs
                    </button>
                </form>
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
                        iconStyle={"square"}
                    />
                )}
                {!selectedKey && (
                    <div className="flex items-center justify-center text-gray-500 h-full w-full">
                        Vyber konfigurační klíč k editaci
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
