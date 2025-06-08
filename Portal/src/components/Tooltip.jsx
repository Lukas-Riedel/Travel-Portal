export default function Tooltip({ children }) {
    return (
        <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:flex
        items-center gap-1 bg-black text-white text-sm px-2 py-1 rounded whitespace-nowrap z-10">
            {children}
        </div>
    )
}