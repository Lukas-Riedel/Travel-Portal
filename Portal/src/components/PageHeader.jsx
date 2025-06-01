import { LocationEdit, SquarePen } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showInputToast from "./InputToast"
import { getPrettyName } from "../utils/helpers"

export default function PageHeader({ name, categories, onNameChanged, onAddressChanged }) {
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

    return categories.length === 1 ? (
        <div className="flex justify-between items-start mb-6">
            <div className="flex items-center space-x-3">
                <h1 className="text-5xl font-bold">
                    {getPrettyName(name)}
                </h1>
                {onNameChanged && isAdmin() && (
                    <button
                        onClick={handleNameChanged}
                        className="mt-1 btn-chip-gray">
                        <SquarePen size={16} />
                    </button>)}
                {onAddressChanged && isAdmin() && (
                    <button
                        onClick={handleAddressChanged}
                        className="mt-1 btn-chip-gray">
                        <LocationEdit size={16} />
                    </button>)}
            </div>
            <div className="flex">
                {categories.map((category, index) => (
                    <img
                        key={index}
                        src={`/img/flags/${category.metadata.unicode}.svg`}
                        alt={category.name}
                        className="w-14 object-cover mx-2" />
                ))}
            </div>
        </div>
    ) : (
        <div className="mb-6">
            <div className="flex justify-center items-start mb-4">
                <div className="flex items-center space-x-3">
                    <h1 className="text-5xl mb-3 font-bold text-center">
                        {getPrettyName(name)}
                    </h1>
                    {onNameChanged && isAdmin() && (
                        <button
                            onClick={handleNameChanged}
                            className="mt-1 btn-chip-gray">
                            <SquarePen size={16} />
                        </button>
                    )}
                    {onAddressChanged && isAdmin() && (
                        <button
                            onClick={handleAddressChanged}
                            className="mt-1 btn-chip-gray">
                            <LocationEdit size={16} />
                        </button>
                    )}
                </div>
            </div>
            <div className="flex flex-wrap justify-center gap-3">
                {categories.map((category, index) => (
                    <img
                        key={index}
                        src={`/img/flags/${category.metadata.unicode}.svg`}
                        alt={category.name}
                        className="w-10 h-auto"
                    />
                ))}
            </div>
        </div>
    )
}