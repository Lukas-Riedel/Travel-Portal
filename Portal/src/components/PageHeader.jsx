import { LocationEdit, SquarePen } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showInputToast from "./InputToast"

export default function PageHeader({ name, categories, onNameChanged, onAddressChanged }) {
    const { isAdmin } = useAuth()

    const handleNameChange = () => {
        showInputToast("Zadej nové jméno:",
            name,
            "Jméno bylo úspěšně aktualizováno",
            "Nepodařilo se aktualizovat jméno",
            onNameChanged
        )
    }

    const handleAddressChange = () => {
        showInputToast("Zadej novou adresu:",
            name,
            "Adresa byla úspěšně aktualizována",
            "Nepodařilo se aktualizovat adresu",
            onAddressChanged
        )
    }

    return (
        <div className="flex justify-between items-start mb-6">
            <div className="flex items-center space-x-3">
                <h1 className="text-5xl font-bold">
                    {name}
                </h1>
                {onNameChanged && isAdmin() && (
                    <button
                        onClick={handleNameChange}
                        className="rounded-full mt-1 bg-white/80 backdrop-blur-sm text-black shadow-md hover:bg-gray-100 transition-colors px-3 py-1 text-sm font-medium flex items-center space-x-2">
                        <SquarePen size={16} />
                    </button>)}
                {onAddressChanged && isAdmin() && (
                    <button
                        onClick={handleAddressChange}
                        className="rounded-full mt-1 bg-white/80 backdrop-blur-sm text-black shadow-md hover:bg-gray-100 transition-colors px-3 py-1 text-sm font-medium flex items-center space-x-2">
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
        </div >
    )
}