import { useState } from "react"
import ReactJson from "react-json-view"
import { TailSpin } from "react-loader-spinner"
import { useAuth } from "../contexts/AuthContext.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useTranslation } from "react-i18next"

interface ConfigurationEditorProps {
    configuration: Record<string, any> | null
    onConfigurationUpdated?: (key: string, value: any) => Promise<Record<string, any>>
}

export default function ConfigurationEditor({ configuration, onConfigurationUpdated }: ConfigurationEditorProps) {
    const { accessToken } = useAuth()
    const { t } = useTranslation()
    const { showUpdateConfigurationEntryToast } = usePredefinedUserInput()

    const [selectedKey, setSelectedKey] = useState<string | null>(null)

    const formatConfigurationKeyName = (key: string) => key.replace(/([A-Z])/g, " $1").replace(/^./, s => s.toUpperCase()).trim()

    const handleConfigurationUpdated = (value: any) => {
        if (selectedKey && onConfigurationUpdated) {
            showUpdateConfigurationEntryToast(() => onConfigurationUpdated(selectedKey, value))
        }
    }

    if (!configuration) {
        return (
            <div className="flex justify-center items-center min-h-[400px]">
                <TailSpin
                    color="black"
                    height={80}
                    width={80} />
            </div>
        )
    }

    return (
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
                    action={(window.env?.VITE_IAM_BASE_URL || import.meta.env.VITE_IAM_BASE_URL) + "/google/auth"}
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
                            const rawValue = edit.updated_src[selectedKey]

                            if (typeof rawValue === "string" && rawValue.trim() !== "") {
                                const parsedNumber = Number(rawValue)
                                newConfig[selectedKey] = !Number.isNaN(parsedNumber) ? parsedNumber : rawValue
                            }
                            else {
                                newConfig[selectedKey] = rawValue
                            }

                            handleConfigurationUpdated(newConfig[selectedKey])
                            return true
                        }}
                        enableClipboard={false}
                        displayDataTypes={false}
                        displayObjectSize={false}
                        quotesOnKeys={false}
                        indentWidth={4}
                        iconStyle={"square"}
                    />
                )}
                {!selectedKey && (
                    <div className="flex items-center justify-center text-gray-500 h-full w-full">
                        {t("configuration.select")}
                    </div>
                )}
            </div>
        </div>
    )
}
