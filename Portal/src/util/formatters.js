export function formatKilometers(value) {
    return format(value, ["kilometr", "kilometry", "kilometrů"])
}

function format(value, forms) {
    return `${value} ${value === 1 ? forms[0] : (value >= 2 && value <= 4) ? forms[1] : forms[2]}`
}