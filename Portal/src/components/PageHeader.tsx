import { Images, RefreshCcw, Share2, SquarePen, Trash2 } from "lucide-react"
import React, { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import type { Category, Highlight } from "../types/CoreSwaggerTypes.ts"
import AppLink from "./AppLink.tsx"
import { StaticNavigationTarget } from "../types/StaticNavigationTarget.ts"
import CategoryFlag from "./CategoryFlag.tsx"

const MOBILE_WIDTH_THRESHOLD = 768

interface PageHeaderProps<T> {
    name: string
    categories?: Category[]
    internalAttributes?: string[]
    onNameChanged?: (name: string) => Promise<T>
    onRemoved?: () => Promise<void>
    onHighlightsRefreshed?: () => Promise<Highlight[]>
}

export default function PageHeader<T,>({ name, categories, internalAttributes, onNameChanged, onRemoved, onHighlightsRefreshed }: PageHeaderProps<T>) {
    const { showRemoveEntityToast, showUpdateEntityNameToast, showSelectHighlightsToast } = usePredefinedUserInput()

    const [isMobile, setIsMobile] = useState(false)

    useEffect(() => {
        const onResize = () => setIsMobile(window.innerWidth < MOBILE_WIDTH_THRESHOLD)

        onResize()
        window.addEventListener("resize", onResize)
        return () => window.removeEventListener("resize", onResize)
    }, [])

    const handleNameChanged = () => {
        if (onNameChanged) {
            showUpdateEntityNameToast(name, onNameChanged)
        }
    }

    const handleHighlightsRefreshed = () => {
        if (onHighlightsRefreshed) {
            showSelectHighlightsToast(onHighlightsRefreshed)
        }
    }

    const handleRemoved = () => {
        if (onRemoved) {
            showRemoveEntityToast(onRemoved)
        }
    }

    const handleShared = () => {
        if (typeof Android !== "undefined" && Android.share) {
            Android.share(name, location.href)
        }
        else if (navigator.share) {
            navigator.share({
                title: name,
                url: location.href
            })
        }
    }

    const renderButtons = () => (
        <>
            {((typeof Android !== "undefined" && Android.share) || navigator.share) && (
                <button
                    onClick={handleShared}
                    className="btn-chip-gray">
                    <Share2 size={16} />
                </button>
            )}
            {onHighlightsRefreshed && (
                <>
                    <AppLink
                        to={StaticNavigationTarget.Highlights}
                        className="btn-chip-gray">
                        <Images size={16} />
                    </AppLink>
                    <button
                        onClick={handleHighlightsRefreshed}
                        className="btn-chip-gray">
                        <RefreshCcw size={16} />
                    </button>
                </>
            )}
            {onNameChanged && (
                <button
                    onClick={handleNameChanged}
                    className="btn-chip-gray">
                    <SquarePen size={16} />
                </button>
            )}
            {onRemoved && (
                <button
                    onClick={handleRemoved}
                    className="btn-chip-gray">
                    <Trash2 size={16} />
                </button>
            )}
        </>
    )

    const renderInternalAttributes = () => Object.keys(internalAttributes)
        .filter(key => internalAttributes[key]).map((key, index) => (
            <React.Fragment key={index}>
                {index > 0 && <span>•</span>}
                <span>{`${key}: ${internalAttributes[key]}`}</span>
            </React.Fragment>
        ))

    return !isMobile && (!categories || categories.length <= 5) ? (
        <div className="flex justify-between items-center mb-5">
            <div>
                <h1 className="text-5xl font-bold leading-tight text-left [text-wrap:balance]">
                    {name && (
                        <>
                            {getEntityPrettyName(name)}
                            {!!(onHighlightsRefreshed || onNameChanged || onRemoved) && (
                                <span className="inline-flex ml-4 align-middle relative -top-[2px] space-x-2">
                                    {renderButtons()}
                                </span>)}
                        </>
                    )}
                </h1>
                {internalAttributes && Object.values(internalAttributes).filter(Boolean).length > 0 && (
                    <div className="flex items-center text-md mt-1 text-gray-600 space-x-2">
                        {renderInternalAttributes()}
                    </div>
                )}
            </div>
            <div className="flex">
                {categories?.map(category => (
                    <CategoryFlag
                        category={category}
                        className="w-14 object-cover mx-2 flex-shrink-0" />
                ))}
            </div>
        </div>
    ) : (
        <div className="mb-5">
            <div className="flex justify-center items-start">
                <h1 className="text-5xl text-center font-bold leading-tight mb-3">
                    {name && (
                        <>
                            {getEntityPrettyName(name)}
                            {!!(onHighlightsRefreshed || onNameChanged || onRemoved) && (
                                <span className="inline-flex ml-4 align-middle relative -top-[2px] space-x-2">
                                    {renderButtons()}
                                </span>)}
                        </>
                    )}
                </h1>
            </div>
            <div className="flex flex-wrap justify-center gap-3">
                {categories?.map(category => (
                    <CategoryFlag
                        category={category}
                        className="w-10 h-auto flex-shrink-0" />
                ))}
            </div>
            {internalAttributes && Object.values(internalAttributes).filter(Boolean).length > 0 && (
                <div className="flex justify-center items-center text-md my-2 text-gray-600 space-x-2">
                    {renderInternalAttributes()}
                </div>
            )}
        </div>
    )
}