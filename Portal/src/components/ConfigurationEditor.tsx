import { useMemo, useState } from "react"
import ReactJson from "react-json-view"
import { TailSpin } from "react-loader-spinner"
import { useAuth } from "../contexts/AuthContext.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useTranslation } from "react-i18next"
import Editor from "./Editor.tsx"

interface ConfigurationEditorProps {
    configuration: Record<string, any> | null
    onConfigurationUpdated?: (key: string, value: any) => Promise<Record<string, any>>
}

export default function ConfigurationEditor({ configuration, onConfigurationUpdated }: ConfigurationEditorProps) {
    const { t } = useTranslation()
    const { showUpdateConfigurationEntryToast } = usePredefinedUserInput()

    const [selectedKey, setSelectedKey] = useState<string | null>(null)

    const formatConfigurationKeyName = (key: string) => key.replace(/([A-Z])/g, " $1").replace(/^./, s => s.toUpperCase()).trim()

    const handleConfigurationUpdated = (value: any) => {
        if (selectedKey && onConfigurationUpdated) {
            showUpdateConfigurationEntryToast(() => onConfigurationUpdated(selectedKey, value))
        }
    }

    const keys = useMemo(() => configuration && Object.keys(configuration).map(key => ({ name: key, label: formatConfigurationKeyName(key) })), [configuration])

    return (
        <Editor
            keys={keys}
            onKeySelected={setSelectedKey}>
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
                    iconStyle={"square"} />
            )}
        </Editor>
    )
}
