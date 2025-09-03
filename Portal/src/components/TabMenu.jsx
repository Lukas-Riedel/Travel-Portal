export default function TabMenu({ labels, activeTab, setActiveTab }) {
    return (
        <nav className="flex flex-wrap border-b border-gray-200">
            {labels.map((label, index) => (
                <button
                    key={index}
                    onClick={() => setActiveTab(index)}
                    className={`flex-1 relative block mt-2 py-2 px-4 font-medium text-center transition-colors duration-200
                        ${activeTab === index
                            ? "text-blue-700 after:absolute after:left-0 after:bottom-0 after:h-0.5 after:w-full after:bg-blue-700"
                            : "text-gray-700 hover:text-blue-700 hover:after:absolute hover:after:left-0 hover:after:bottom-0 hover:after:h-0.5 hover:after:w-full hover:after:bg-blue-600"}`}>
                    {label}
                </button>
            ))}
        </nav>
    )
}
