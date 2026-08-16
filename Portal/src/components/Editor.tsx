import { useCallback, useEffect, useMemo, useState } from "react";
import type { EditorKey } from "../types/EditorKey";
import { useTranslation } from "react-i18next";
import { TailSpin } from "react-loader-spinner";
import { useSearchParams } from "react-router-dom";
import AppLink from "./AppLink";
import { ExternalLink } from "lucide-react";

const KEY_URL_QUERY_PARAM_NAME = "key"

interface EditorProps {
    keys: EditorKey[] | null
    children: React.ReactNode
    onKeySelected?: (key: string) => void
}

export default function Editor({ keys, children, onKeySelected }: EditorProps) {
    const { t } = useTranslation()
    const [searchParams, setSearchParams] = useSearchParams()

    const keyNames = useMemo(() => keys?.map(key => key.name), [keys])

    const activeKeyName = useMemo(() => {
        const keyNameFromUrl = searchParams.get(KEY_URL_QUERY_PARAM_NAME)
        if (keyNameFromUrl && keyNames?.includes(keyNameFromUrl)) {
            return keyNameFromUrl
        }

        return null
    }, [keyNames, searchParams])

    // TODO: Extract URL search params logic into a new hook, and make Editor a pure controlled component.
    const setActiveKey = useCallback((name: string) => {
        const newSearchParams = new URLSearchParams(searchParams)
        newSearchParams.set(KEY_URL_QUERY_PARAM_NAME, name)
        setSearchParams(newSearchParams)
    }, [keyNames, searchParams, setSearchParams])

    useEffect(() => {
        if (onKeySelected && activeKeyName) {
            onKeySelected(activeKeyName)
        }
    }, [keys, keyNames, activeKeyName])

    if (!keys) {
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
                {keys.map(({ name, label, target }) => (
                    <div
                        key={name}
                        onClick={() => setActiveKey(name)}
                        className={`flex items-center justify-between w-full text-left px-3 py-2 rounded cursor-pointer transition-colors group ${name === activeKeyName ? "bg-gray-300 font-semibold text-gray-900" : "text-gray-700 hover:bg-gray-200"}`}>
                        <span className="truncate">
                            {label}
                        </span>
                        {target && (
                            <AppLink
                                to={target}
                                onClick={e => e.stopPropagation()}
                                className={`ml-2 p-1 rounded transition-all ${name === activeKeyName ? "text-gray-600 hover:text-black hover:bg-gray-400/50" : "text-gray-400 hover:text-gray-700 hover:bg-gray-300/50 opacity-0 group-hover:opacity-100"}`}>
                                <ExternalLink className="w-4 h-4" />
                            </AppLink>
                        )}
                    </div>
                ))}
            </div>
            <div className="flex-1 p-3 overflow-auto">
                {activeKeyName ? children : (
                    <div className="flex items-center justify-center text-gray-500 h-full w-full">
                        {t("editor.select")}
                    </div>
                )}
            </div>
        </div>
    )
}