export function getOnlyElement<T>(arr?: T[]): T | undefined {
    return arr?.length === 1 ? arr[0] : undefined
}