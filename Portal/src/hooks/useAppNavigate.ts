import { useLocation, useNavigate } from "react-router-dom"
import type { UseAppNavigateResult } from "../types/UseAppNavigateResult.ts"
import { getPath } from "../utils/navigationUtils.ts"

export const useAppNavigate = (): UseAppNavigateResult => {
    const { pathname } = useLocation()
    const navigate = useNavigate()

    return to => navigate(getPath(to, pathname))
}