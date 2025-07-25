import { Trash2, Plus, Bold, Italic, Link, Underline } from "lucide-react"
import showConfirmToast from "./ConfirmToast"
import { useState, useRef, useMemo } from "react"
import { TailSpin } from "react-loader-spinner"
import { useAuth } from "../contexts/AuthContext"
import { getDateTimeString } from "../utils/helpers"

const loadingNotesCount = 3

export default function NoteBar({ notes, onNoteCreated, onNoteRemoved }) {
    const { isAdmin } = useAuth()

    const [newNoteContent, setNewNoteContent] = useState("")
    const textareaRef = useRef(null)

    const handleDelete = noteId => {
        showConfirmToast(
            "Opravdu chceš odstranit vybranou poznámku?",
            "Poznámka byla úspěšně odstraněna",
            "Nepodařilo se odstranit poznámku",
            async () => onNoteRemoved(noteId)
        )
    }

    const handleCreate = () => {
        const content = newNoteContent.trim()
        if (!content) {
            return
        }

        showConfirmToast(
            "Opravdu chceš přidat novou poznámku?",
            "Poznámka byla úspěšně přidána",
            "Nepodařilo se přidat poznámku",
            async () => onNoteCreated(content).then(() => setNewNoteContent(""))
        )
    }

    const insertAtCursor = (before, after = "") => {
        const textarea = textareaRef.current
        if (!textarea) {
            return
        }

        const { selectionStart, selectionEnd, value } = textarea
        setNewNoteContent(value.slice(0, selectionStart) + before + value.slice(selectionStart, selectionEnd) + after + value.slice(selectionEnd))

        const newCursorPosition = selectionStart + before.length
        setTimeout(() => {
            textarea.setSelectionRange(newCursorPosition, newCursorPosition)
            textarea.focus()
        }, 0)
    }

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 my-4">
            {notes ? notes.map(note => (
                <div
                    key={note.id}
                    className="relative bg-white rounded-xl shadow-md p-4 min-h-[100px] flex flex-col justify-between">
                    <div
                        className="prose prose-sm max-w-none text-gray-800 mb-6"
                        dangerouslySetInnerHTML={{ __html: note.content }} />
                    <span className="absolute bottom-4 left-4 text-sm text-gray-400">
                        {getDateTimeString(note.timestamp)}
                    </span>
                    {onNoteRemoved &&isAdmin && (
                        <div className="absolute bottom-2 right-2">
                            <button
                                onClick={() => handleDelete(note.id)}
                                className="p-1 rounded hover:bg-gray-100 transition-colors">
                                <Trash2 size={16} />
                            </button>
                        </div>
                    )}
                </div>
            )) : (Array.from({ length: loadingNotesCount - (onNoteCreated && isAdmin ? 1 : 0) })
                .map((_, index) => (
                    <div
                        key={index}
                        className="flex flex-col items-center justify-center relative bg-white rounded-xl shadow-md p-4 min-h-[100px]">
                        <TailSpin
                            color="black"
                            height={48}
                            width={48} />
                    </div>
                ))
            )}
            {onNoteCreated && isAdmin && (
                <div className="relative bg-white rounded-xl shadow-md p-4 min-h-[100px] flex flex-col">
                    <textarea
                        ref={textareaRef}
                        className="w-full resize-none border border-gray-300 rounded-md p-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400 mb-2 flex-grow"
                        placeholder="Nová poznámka"
                        value={newNoteContent}
                        onChange={e => setNewNoteContent(e.target.value)} />
                    <div className="mt-2 flex items-center">
                        <div className="flex space-x-1">
                            <button
                                className="p-1 rounded hover:bg-gray-100"
                                onClick={() => insertAtCursor("<strong>", "</strong>")}
                                title="Tučně">
                                <Bold size={16} />
                            </button>
                            <button
                                className="p-1 rounded hover:bg-gray-100"
                                onClick={() => insertAtCursor("<em>", "</em>")}
                                title="Kurzíva">
                                <Italic size={16} />
                            </button>
                            <button
                                className="p-1 rounded hover:bg-gray-100"
                                onClick={() => insertAtCursor("<u>", "</u>")}
                                title="Podtržení">
                                <Underline size={16} />
                            </button>
                            <button
                                className="p-1 rounded hover:bg-gray-100"
                                onClick={() => insertAtCursor('<a href="">', "</a>")}
                                title="Odkaz">
                                <Link size={16} />
                            </button>
                        </div>
                        <button
                            className="p-1 rounded hover:bg-gray-100 ml-auto"
                            onClick={handleCreate}>
                            <Plus size={16} />
                        </button>
                    </div>
                </div>
            )}
        </div>
    )
}
