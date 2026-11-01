import { useTranslation } from "react-i18next"

import type { Note } from "../types/CoreSwaggerTypes.ts"
import NoteCardGrid from "./NoteCardGrid.tsx"

const GROUP_DEFINITION_PATTERN = /^[ \t]{0,3}#(?!#)[ \t]+(.+?)[ \t]*(?:#+[ \t]*)?(?:\r?\n|$)/

interface NoteGroupCardGridProps {
    notes: Note[] | null
    rowSize: number
    columnSize?: number
    onNoteCreated?: (content: string) => Promise<Note>
    onNoteContentUpdated?: (noteId: string, content: string) => Promise<Note>
    onNoteRemoved?: (noteId: string) => Promise<void>
}

export default function NoteGroupCardGrid({ notes, rowSize, columnSize, onNoteCreated, onNoteContentUpdated, onNoteRemoved }: NoteGroupCardGridProps) {
    const { t } = useTranslation()

    const getNoteDisplayContent = (note: Note) => note.content.replace(GROUP_DEFINITION_PATTERN, "").trimStart()
    const groupedNotes = (notes ?? []).reduce((groups, note) => {
        const match = note.content.match(GROUP_DEFINITION_PATTERN)
        const title = match ? (match[1] ?? "").trim() : t("note.group.other")
        const hasHeading = !!match
        const key = title.toLocaleLowerCase()
        const group = groups.get(key) ?? { title, notes: [], hasHeading }
        group.notes.push(note)
        group.hasHeading ||= hasHeading
        groups.set(key, group)
        return groups
    }, new Map<string, { title: string, notes: Note[], hasHeading: boolean }>())

    const groups = groupedNotes.size > 0 ? Array.from(groupedNotes.values()) : [{ title: t("note.group.other"), notes: [], hasHeading: false }]
    groups.sort((first, second) => first.title.localeCompare(second.title, undefined, { sensitivity: "base" }))

    const soleGroup = groups.length === 1 ? groups[0] : undefined
    if (!notes || (soleGroup && !soleGroup.hasHeading)) {
        return (
            <NoteCardGrid
                rowSize={rowSize}
                columnSize={columnSize}
                notes={soleGroup?.notes ?? null}
                onNoteCreated={onNoteCreated}
                onNoteContentUpdated={onNoteContentUpdated}
                onNoteRemoved={onNoteRemoved} />
        )
    }

    return (
        <div className="my-4 space-y-4">
            {groups.map(group => (
                <details
                    key={group.title}
                    className="group">
                    <summary className="cursor-pointer list-none rounded-md bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                        <span className="mr-2 inline-block transition-transform group-open:rotate-90">
                            ▶
                        </span>
                        {group.title}
                        {" "}
                        <span className="font-normal text-gray-400">
                            ({group.notes.length})
                        </span>
                    </summary>
                    <NoteCardGrid
                        rowSize={rowSize}
                        columnSize={columnSize}
                        notes={group.notes}
                        displayContentSelector={getNoteDisplayContent}
                        newNoteContent={group.hasHeading ? `# ${group.title}\n` : undefined}
                        onNoteCreated={onNoteCreated}
                        onNoteContentUpdated={onNoteContentUpdated}
                        onNoteRemoved={onNoteRemoved} />
                </details>
            ))}
        </div>
    )
}
