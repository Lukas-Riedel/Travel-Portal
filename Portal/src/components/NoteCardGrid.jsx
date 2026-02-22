import CardGrid from "./CardGrid";
import NoteCard from "./NoteCard";

export default function NoteCardGrid({ notes, onNoteCreated, onNoteContentUpdated, onNoteRemoved }) {
    return (
        <CardGrid cardsPerRowCount={3}>
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