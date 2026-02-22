export interface BranchingToastBranch {
    name: string
    handle: () => Promise<boolean>
}