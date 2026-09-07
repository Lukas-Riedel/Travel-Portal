import { useLocation, useNavigate } from "react-router-dom"

import { AppLinkTarget } from "../types/AppLinkTarget.ts"
import type { UseAppNavigateResult } from "../types/UseAppNavigateResult.ts"
import { getPath } from "../utils/navigationUtils.ts"

export const useAppNavigate = (target: AppLinkTarget = AppLinkTarget.Default): UseAppNavigateResult => {
    const { pathname } = useLocation()
    const navigate = useNavigate()

    return to => navigate(getPath(to, target, pathname))
}