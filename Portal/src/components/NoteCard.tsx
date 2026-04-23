import { useEffect, useMemo, useRef, useState } from "react"
import { Trash2, Plus, Bold, Italic, Link, Edit2, Check } from "lucide-react"
import { useTranslation } from "react-i18next"
import ReactMarkdown from "react-markdown"
import { getDateTimeString } from "../utils/helpers.js"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import LoadingCard from "./LoadingCard.tsx"
import type { Note } from "../types/CoreSwaggerTypes.ts"
import Card from "./Card.tsx"

interface NoteCardProps {
    note?: Note | null
    onNoteCreated?: (content: string) => Promise<Note>
    onNoteContentUpdated?: (noteId: string, content: string) => Promise<Note>
    onNoteRemoved?: (noteId: string) => Promise<void>
}

export default function NoteCard({ note, onNoteCreated, onNoteContentUpdated, onNoteRemoved }: NoteCardProps) {
    const { t } = useTranslation()
    const { showCreateNoteToast, showRemoveNoteToast, showUpdateNoteToast } = usePredefinedUserInput()

    const textareaRef = useRef<HTMLTextAreaElement | null>(null)
    const [isBeingEdited, setIsBeingEdited] = useState(!!onNoteCreated)

    const handleDelete = () => {
        if (note?.id) {
            showRemoveNoteToast(() => onNoteRemoved(note.id))
        }
    }

    const handleCreate = () => {
        const content = textareaRef.current?.value.trim()
        if (!content) {
            return
        }

        showCreateNoteToast(() => onNoteCreated(content).then(note => {
            if (textareaRef.current) {
                textareaRef.current.value = ""
            }

            return note
        }))
    }

    const handleUpdate = () => {
        const content = textareaRef.current?.value.trim()
        if (!content || !note?.id) {
            return
        }

        if (content === note.content) {
            setIsBeingEdited(false)
            return
        }

        showUpdateNoteToast(() => onNoteContentUpdated(note.id, content).then(note => {
            if (textareaRef.current) {
                textareaRef.current.value = ""
            }

            setIsBeingEdited(false)
            return note
        }))
    }

    const insertAtCursor = (before: string, after: string) => {
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

    if (!note && !onNoteCreated) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card className="relative min-h-[100px] flex flex-col justify-between">
            {isBeingEdited ? (
                <textarea
                    ref={textareaRef}
                    defaultValue={note?.content}
                    placeholder={!note && t("note.placeholder.new")}
                    onInput={adjustHeight}
                    className="w-full resize-none border border-gray-300 rounded-md p-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400 mb-2 flex-grow" />
            ) : (
                note && (
                    <div className="prose prose-sm max-w-none text-gray-800 mb-6">
                        <ReactMarkdown>
                            {note.content}
                        </ReactMarkdown>
                    </div>
                )
            )}
            <div className="mt-2 flex items-center justify-between">
                {!isBeingEdited && note && (
                    <span className="text-sm text-gray-400">
                        {getDateTimeString(note.timestamp)}
                    </span>
                )}
                {!!(onNoteCreated || onNoteContentUpdated || onNoteRemoved) && (
                    <>
                        <div className="flex space-x-1">
                            {isBeingEdited && (
                                <>
                                    <button
                                        className="p-1 rounded hover:bg-gray-100"
                                        onClick={() => insertAtCursor("**", "**")}>
                                        <Bold size={16} />
                                    </button>
                                    <button
                                        className="p-1 rounded hover:bg-gray-100"
                                        onClick={() => insertAtCursor("*", "*")}>
                                        <Italic size={16} />
                                    </button>
                                    <button
                                        className="p-1 rounded hover:bg-gray-100"
                                        onClick={() => insertAtCursor('[Placeholder](', ")")}>
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
                                            onClick={handleUpdate}
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
                                            onClick={handleDelete}
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
        </Card>
    )
}
