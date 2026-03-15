import { Link, useLocation, type LinkProps } from "react-router-dom"
import type { Navigable } from "../types/Navigable.ts"
import { getPath } from "../utils/navigationUtils.ts"

interface AppLinkProps extends Omit<LinkProps, "to"> {
    to: Navigable
}

export default function AppLink({ to, children, ...props }: AppLinkProps) {
    const { pathname } = useLocation()

    return (
        <Link
            to={getPath(to, pathname)}
            title={typeof children === "string" ? children : undefined}
            {...props}>
            {children}
        </Link>
    )
}