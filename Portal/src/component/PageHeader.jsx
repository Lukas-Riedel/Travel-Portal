export default function PageHeader({ name, categories }) {
    return (
        <div className="flex justify-between items-start mb-6">
            <h1 className="text-5xl font-bold">
                {name}
            </h1>
            <div className="flex">
                {categories.map((category, index) => (
                    <img
                        key={index}
                        src={`/flags/${category.metadata.unicode}.svg`}
                        alt={category.name}
                        className="w-14 object-cover mx-2" />
                ))}
            </div>
        </div>
    )
}