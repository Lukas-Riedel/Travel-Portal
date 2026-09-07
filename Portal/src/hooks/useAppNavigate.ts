import { useLocation, useNavigate } from "react-router-dom"
import type { UseAppNavigateResult } from "../types/UseAppNavigateResult.ts"
import { getPath } from "../utils/navigationUtils.ts"
import { AppLinkTarget } from "../types/AppLinkTarget.ts"

export const useAppNavigate = (target: AppLinkTarget = AppLinkTarget.Default): UseAppNavigateResult => {
    const { pathname } = useLocation()
    const navigate = useNavigate()

    return to => navigate(getPath(to, target, pathname))
}