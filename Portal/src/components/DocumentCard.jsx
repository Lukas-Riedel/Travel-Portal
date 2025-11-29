
import { Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import LoadingCard from "./LoadingCard"
import { getDateString } from "../utils/helpers"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

export default function DocumentCard({ document, onDocumentRemoved }) {
    const { isAdmin } = useAuth()
    const { showRemoveDocumentToast } = usePredefinedUserInput()

    const handleDelete = () => {
        showRemoveDocumentToast(() => onDocumentRemoved(document.id))
    }

    const documentProperties = {
        "Identifikátor": document.code,
        "Vydavatel": document.issuer,
        "Expirace": getDateString(document.expiration)
    }

    return document ? (
        <div className="bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full space-y-1">
            <div className="flex justify-start items-center">
                <span className="text-lg font-semibold">
                    {document.name}
                </span>
                {isAdmin && onDocumentRemoved && (
                    <button
                        onClick={() => handleDelete(document)}
                        className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
            <ul className="space-y-0.5 mt-2">
                {Object.entries(documentProperties).filter(([key]) => documentProperties[key]).map(([key, value]) => (
                    <li
                        key={key}
                        className="text-gray-700">
                        <span className="font-semibold">
                            {key}:
                        </span>
                        {" "}
                        <span dangerouslySetInnerHTML={{ __html: value }} />
                    </li>
                ))}
            </ul>
        </div>
    ) : (
        <LoadingCard />
    )
}
