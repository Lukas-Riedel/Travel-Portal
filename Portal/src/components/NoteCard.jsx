import { Trash2, Plus, Bold, Italic, Link, Edit2, Check } from "lucide-react"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { useEffect, useRef, useState } from "react"
import { useAuth } from "../contexts/AuthContext"
import { getDateTimeString } from "../utils/helpers"
import ReactMarkdown from "react-markdown"

export default function NoteCard({ note, onNoteCreated, onNoteContentUpdated, onNoteRemoved }) {
    const { isAdmin } = useAuth()
    const { showConfirmToast } = useUserInput()

    const textareaRef = useRef(null)
    const [isBeingEdited, setIsBeingEdited] = useState(onNoteCreated !== undefined)

    const handleDelete = () => {
        showConfirmToast(
            "Opravdu chceš odstranit vybranou poznámku?",
            async () => onNoteRemoved(note.id),
            "Poznámka byla úspěšně odstraněna",
            "Nepodařilo se odstranit poznámku"
        )
    }

    const handleCreate = () => {
        const content = textareaRef.current.value.trim()
        if (!content) {
            return
        }

        showConfirmToast(
            "Opravdu chceš přidat novou poznámku?",
            async () => onNoteCreated(content).then(() => {
                textareaRef.current.value = ""
            }),
            "Poznámka byla úspěšně přidána",
            "Nepodařilo se přidat poznámku"
        )
    }

    const handleUpdate = () => {
        const content = textareaRef.current.value.trim()
        if (!content) {
            return
        }

        if (content === note.content) {
            setIsBeingEdited(false)
            return
        }

        showConfirmToast(
            "Opravdu chceš upravit vybranou poznámku?",
            async () => onNoteContentUpdated(note.id, content).then(() => {
                textareaRef.current.value = ""
                setIsBeingEdited(false)
            }),
            "Poznámka byla úspěšně upravena",
            "Nepodařilo se upravit poznámku"
        )
    }

    const insertAtCursor = (before, after = "") => {
        const textarea = textareaRef.current
        if (!textarea) {
            return
        }

        const { selectionStart, selectionEnd, value } = textarea
        const newValue = value.slice(0, selectionStart) + before + value.slice(selectionStart, selectionEnd) + after + value.slice(selectionEnd)

        textarea.value = newValue

        const newCursorPosition = selectionStart + before.length

        textarea.setSelectionRange(newCursorPosition, newCursorPosition)
        textarea.focus()
    }

    const adjustHeight = () => {
        const textarea = textareaRef.current
        if (!textarea) {
            return
        }
        textarea.style.height = "auto"
        textarea.style.height = textarea.scrollHeight + "px"
    }

    useEffect(() => {
        adjustHeight()
    }, [note?.content])

    return (note || onNoteCreated) ? (
        <div className="relative bg-white rounded-xl shadow-md p-4 min-h-[100px] flex flex-col justify-between">
            {isBeingEdited ? (
                <textarea
                    ref={textareaRef}
                    defaultValue={note?.content}
                    placeholder={!note && "Nová poznámka"}
                    onInput={adjustHeight}
                    className="w-full resize-none border border-gray-300 rounded-md p-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400 mb-2 flex-grow" />
            ) : (
                <div className="prose prose-sm max-w-none text-gray-800 mb-6">
                    <ReactMarkdown>
                        {note.content}
                    </ReactMarkdown>
                </div>
            )}
            <div className="mt-2 flex items-center justify-between">
                {!isBeingEdited && note && (
                    <span className="text-sm text-gray-400">
                        {getDateTimeString(note.timestamp)}
                    </span>
                )}
                {isAdmin && (
                    <>
                        <div className="flex space-x-1">
                            {isBeingEdited && (
                                <>
                                    <button
                                        className="p-1 rounded hover:bg-gray-100"
                                        onClick={() => insertAtCursor("**", "**")}
                                        title="Tučně">
                                        <Bold size={16} />
                                    </button>
                                    <button
                                        className="p-1 rounded hover:bg-gray-100"
                                        onClick={() => insertAtCursor("*", "*")}
                                        title="Kurzíva">
                                        <Italic size={16} />
                                    </button>
                                    <button
                                        className="p-1 rounded hover:bg-gray-100"
                                        onClick={() => insertAtCursor('[Odkaz](', ")")}
                                        title="Odkaz">
                                        <Link size={16} />
                                    </button>
                                </>
                            )}
                        </div>
                        <div className="flex space-x-1">
                            {note ? (
                                <>
                                    {isBeingEdited && (
                                        <button
                                            onClick={() => handleUpdate(note.id)}
                                            className="p-1 rounded hover:bg-gray-100 transition-colors">
                                            <Check size={16} />
                                        </button>
                                    )}
                                    {!isBeingEdited && onNoteContentUpdated && (
                                        <button
                                            onClick={() => setIsBeingEdited(previous => !previous)}
                                            className="p-1 rounded hover:bg-gray-100 transition-colors">
                                            <Edit2 size={16} />
                                        </button>
                                    )}
                                    {onNoteRemoved && (
                                        <button
                                            onClick={() => handleDelete(note.id)}
                                            className="p-1 rounded hover:bg-gray-100 transition-colors">
                                            <Trash2 size={16} />
                                        </button>
                                    )}
                                </>
                            ) : (
                                <>
                                    {onNoteCreated && (
                                        <button
                                            className="p-1 rounded hover:bg-gray-100 ml-auto"
                                            onClick={handleCreate}>
                                            <Plus size={16} />
                                        </button>
                                    )}
                                </>
                            )}
                        </div>
                    </>
                )}
            </div>
        </div>
    ) : (
        <LoadingCard />
    )
}