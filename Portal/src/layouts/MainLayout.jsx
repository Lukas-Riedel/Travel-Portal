export default function MainLayout({ children }) {
    return (
        <div className="max-w-6xl mt-8 mb-8 rounded-2xl mx-auto p-8 bg-white text-gray-900">
            {children}
        </div>
    )
}