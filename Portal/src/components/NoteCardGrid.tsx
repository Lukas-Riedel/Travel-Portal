import type { Note } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import NoteCard from "./NoteCard.tsx"

interface NoteCardGridProps {
    notes: Note[] | null
    rowSize: number
    columnSize?: number
    onNoteCreated?: (content: string) => Promise<Note>
    onNoteContentUpdated?: (noteId: string, content: string) => Promise<Note>
    onNoteRemoved?: (noteId: string) => Promise<void>
}

export default function NoteCardGrid({ notes, rowSize, columnSize, onNoteCreated, onNoteContentUpdated, onNoteRemoved }: NoteCardGridProps) {
    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
            {notes?.map(note => (
                <NoteCard
                    key={note.id}
                    note={note}
                    onNoteContentUpdated={onNoteContentUpdated}
                    onNoteRemoved={onNoteRemoved} />
            ))?.concat(
                <NoteCard
                    key={"new"}
                    onNoteCreated={onNoteCreated} />
            )}
        </CardGrid>
    )
}