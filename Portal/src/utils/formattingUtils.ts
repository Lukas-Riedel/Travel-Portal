// TODO: Move to some interface common for all entities.
export function getEntityPrettyName(name: string | number) {
    return String(name).replace(/\s*\(.*/, "").trim()
}

export function formatDeviceType(type: string): string {
    return type.toLowerCase().replace(/^./, c => c.toUpperCase())
}