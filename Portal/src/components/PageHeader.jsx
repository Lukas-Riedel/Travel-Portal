import { Images, Share2, SquarePen, Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showInputToast from "./InputToast"
import { getPrettyName } from "../utils/helpers"
import { useUserInput } from "../hooks/useUserInput.ts"
import React, { useEffect, useState } from "react"
import { Link } from "react-router-dom"

export default function PageHeader({ name, categories, internalAttributes, showHighlightsButton, onNameChanged, onRemoved }) {
    const { isAdmin } = useAuth()
    const { showConfirmToast } = useUserInput()

    const [isMobile, setIsMobile] = useState(false)

    useEffect(() => {
        const onResize = () => setIsMobile(window.innerWidth < 768)
        onResize()
        window.addEventListener("resize", onResize)
        return () => window.removeEventListener("resize", onResize)
    }, [])

    const handleNameChanged = () => {
        showInputToast("Zadej nové jméno:",
            name,
            "Jméno bylo úspěšně aktualizováno",
            "Nepodařilo se aktualizovat jméno",
            onNameChanged
        )
    }

    const handleRemoved = () => {
        showConfirmToast(
            "Opravdu chceš odstranit tuto entitu?",
            "Entita byla úspěšně odstraněna",
            "Nepodařilo se odstranit entitu",
            onRemoved
        )
    }

    const handleShared = async () => {
        if (typeof Android !== "undefined" && Android.share) {
            Android.share(name, location.href)
        }
        else {
            await navigator.share({
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
            {showHighlightsButton && (
                <Link
                    to={`${location.pathname}/highlight`}
                    className="btn-chip-gray">
                    <Images size={16} />
                </Link>
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
                            {getPrettyName(name)}
                            {isAdmin && (
                                <span className="inline-flex ml-4 align-middle relative -top-[2px] space-x-2">
                                    {renderButtons()}
                                </span>)}
                        </>
                    )}
                </h1>
                {internalAttributes && isAdmin && Object.values(internalAttributes).filter(Boolean).length > 0 && (
                    <div className="flex items-center text-md mt-1 text-gray-600 space-x-2">
                        {renderInternalAttributes()}
                    </div>
                )}
            </div>
            <div className="flex">
                {categories?.map(category => (
                    <img
                        key={category.id}
                        className="w-14 object-cover mx-2 flex-shrink-0"
                        src={`/img/flags/${category?.metadata?.unicode}.svg`}
                        alt={category?.name}
                    />
                ))}
            </div>
        </div>
    ) : (
        <div className="mb-5">
            <div className="flex justify-center items-start">
                <h1 className="text-5xl text-center font-bold leading-tight mb-3">
                    {name && (
                        <>
                            {getPrettyName(name)}
                            {isAdmin && (
                                <span className="inline-flex ml-4 align-middle relative -top-[2px] space-x-2">
                                    {renderButtons()}
                                </span>)}
                        </>
                    )}
                </h1>
            </div>
            <div className="flex flex-wrap justify-center gap-3">
                {categories?.map(category => (
                    <img
                        key={category.id}
                        src={`/img/flags/${category?.metadata?.unicode}.svg`}
                        alt={category?.name}
                        className="w-10 h-auto flex-shrink-0" />
                ))}
            </div>
            {internalAttributes && isAdmin && Object.values(internalAttributes).filter(Boolean).length > 0 && (
                <div className="flex justify-center items-center text-md my-2 text-gray-600 space-x-2">
                    {renderInternalAttributes()}
                </div>
            )}
        </div>
    )
}