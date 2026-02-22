export default function FloatingButton({ icon: Icon, onClick }) {
    return (
        <button
            onClick={onClick}
            className="fixed bottom-8 right-8 bg-white hover:bg-gray-100 text-black p-3 rounded-full shadow-md transition-colors duration-200">
            <Icon className="w-6 h-6" />
        </button>
    )
}
