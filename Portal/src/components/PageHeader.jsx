import { ArrowRightLeft, LocationEdit, SquarePen, Trash2, Upload } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showInputToast from "./InputToast"
import { getPrettyName } from "../utils/helpers"
import showConfirmToast from "./ConfirmToast"
import showFormToast from "./FormToast"

export default function PageHeader({ name, categories, loadCandidates, onNameChanged, onAddressChanged, onMoved, onLoaded, onRemoved }) {
    const { isAdmin } = useAuth()

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

    return categories?.length <= 1 ? (
        <div className="flex justify-between items-start mb-5">
            <div className="flex items-center space-x-3">
                <h1 className="text-5xl font-bold">
                    {categories && getPrettyName(name)}
                </h1>
                {isAdmin && (
                    <>
                        {onNameChanged && (
                            <button
                                onClick={handleNameChanged}
                                className="mt-1 btn-chip-gray">
                                <SquarePen size={16} />
                            </button>)}
                        {onAddressChanged && (
                            <button
                                onClick={handleAddressChanged}
                                className="mt-1 btn-chip-gray">
                                <LocationEdit size={16} />
                            </button>)}
                        {onMoved && (
                            <button
                                onClick={handleMoved}
                                className="mt-1 btn-chip-gray">
                                <ArrowRightLeft size={16} />
                            </button>
                        )}
                        {onLoaded && (
                            <button
                                onClick={handleLoaded}
                                className="mt-1 btn-chip-gray">
                                <Upload size={16} />
                            </button>
                        )}
                        {onRemoved && (
                            <button
                                onClick={handleRemoved}
                                className="mt-1 btn-chip-gray">
                                <Trash2 size={16} />
                            </button>)}
                    </>
                )}
            </div>
            <div className="flex">
                {categories?.map((category, index) => (
                    <img
                        key={index}
                        src={`/img/flags/${category?.metadata?.unicode}.svg`}
                        alt={category?.name}
                        className="w-14 object-cover mx-2" />
                ))}
            </div>
        </div>
    ) : (
        <div className="mb-6">
            <div className="flex justify-center items-start mb-4">
                <div className="flex items-center space-x-3">
                    <h1 className="text-5xl mb-3 font-bold text-center">
                        {categories && getPrettyName(name)}
                    </h1>
                </div>
            </div>
            <div className="flex flex-wrap justify-center gap-3">
                {categories?.map((category, index) => (
                    <img
                        key={index}
                        src={`/img/flags/${category?.metadata?.unicode}.svg`}
                        alt={category?.name}
                        className="w-10 h-auto" />
                ))}
            </div>
            {isAdmin && (
                <div className="flex flex-wrap justify-center gap-3 my-5">
                    {onNameChanged && (
                        <button
                            onClick={handleNameChanged}
                            className="mt-1 btn-chip-gray">
                            <SquarePen size={16} />
                        </button>
                    )}
                    {onAddressChanged && (
                        <button
                            onClick={handleAddressChanged}
                            className="mt-1 btn-chip-gray">
                            <LocationEdit size={16} />
                        </button>
                    )}
                    {onMoved && (
                        <button
                            onClick={handleMoved}
                            className="mt-1 btn-chip-gray">
                            <ArrowRightLeft size={16} />
                        </button>
                    )}
                    {onLoaded && (
                        <button
                            onClick={handleLoaded}
                            className="mt-1 btn-chip-gray">
                            <Upload size={16} />
                        </button>
                    )}
                    {onRemoved && (
                        <button
                            onClick={handleRemoved}
                            className="mt-1 btn-chip-gray">
                            <Trash2 size={16} />
                        </button>)}
                </div>
            )}
        </div>
    )
}