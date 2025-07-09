import { ArrowRightLeft, LocationEdit, SquarePen, Trash2, Upload } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showInputToast from "./InputToast"
import { getPrettyName } from "../utils/helpers"
import showConfirmToast from "./ConfirmToast"
import showFormToast from "./FormToast"
import { useEffect, useState } from "react"

export default function PageHeader({ name, categories, loadCandidates, onNameChanged, onAddressChanged, onMoved, onLoaded, onRemoved }) {
    const { isAdmin } = useAuth()

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

    const handleAddressChanged = () => {
        showInputToast("Zadej novou adresu:",
            name,
            "Adresa byla úspěšně aktualizována",
            "Nepodařilo se aktualizovat adresu",
            onAddressChanged
        )
    }

    const handleMoved = () => {
        showFormToast(
            "Zadej, o kolik dnů se má entita přesunout:",
            [
                { type: "number", required: true }
            ],
            "Entita byla úspěšně přesunuta",
            "Nepodařilo se přesunout entitu",
            onMoved
        )
    }

    const handleLoaded = () => {
        showFormToast(
            "Vyber entitu k načtení:",
            [
                { type: "select", required: true, options: loadCandidates }
            ],
            "Entita byla úspěšně načtena",
            "Nepodařilo se načíst entitu",
            onLoaded
        )
    }

    const handleRemoved = () => {
        showConfirmToast("Opravdu chceš odstranit tuto entitu?",
            "Entita byla úspěšně odstraněna",
            "Nepodařilo se odstranit entitu",
            onRemoved
        )
    }

    const renderButtons = () => (
        <>
            {onNameChanged && (
                <button
                    onClick={handleNameChanged}
                    className="btn-chip-gray">
                    <SquarePen size={16} />
                </button>)}
            {onAddressChanged && (
                <button
                    onClick={handleAddressChanged}
                    className="btn-chip-gray">
                    <LocationEdit size={16} />
                </button>)}
            {onMoved && (
                <button
                    onClick={handleMoved}
                    className="btn-chip-gray">
                    <ArrowRightLeft size={16} />
                </button>
            )}
            {onLoaded && (
                <button
                    onClick={handleLoaded}
                    className="btn-chip-gray">
                    <Upload size={16} />
                </button>
            )}
            {onRemoved && (
                <button
                    onClick={handleRemoved}
                    className="btn-chip-gray">
                    <Trash2 size={16} />
                </button>)}
        </>
    )

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
        </div>
    )
}