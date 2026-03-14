import CardGrid from "./CardGrid.tsx";
import NoteCard from "./NoteCard";

export default function NoteCardGrid({ notes, onNoteCreated, onNoteContentUpdated, onNoteRemoved }) {
    return (
        <CardGrid rowSize={3}>
            {notes?.map(note => (
                <NoteCard
                    key={note.id}
                    note={note}
                    onNoteContentUpdated={onNoteContentUpdated}
                    onNoteRemoved={onNoteRemoved} />
            ))?.concat(
                <NoteCard onNoteCreated={onNoteCreated} />
            )}
        </CardGrid>
    )
}