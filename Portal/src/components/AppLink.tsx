import type { ReactNode } from "react"
import React from "react"
import { Link, type LinkProps,useLocation } from "react-router-dom"

import { AppLinkTarget } from "../types/AppLinkTarget.ts"
import type { Navigable } from "../types/Navigable.ts"
import { getPath } from "../utils/navigationUtils.ts"

interface AppLinkProps extends Omit<Omit<LinkProps, "target">, "to"> {
    to: Navigable
    target?: AppLinkTarget
}

export default function AppLink({ to, target = AppLinkTarget.Default, children, ...props }: AppLinkProps) {
    const { pathname } = useLocation()

    return (
        <Link
            to={getPath(to, target, pathname)}
            title={getTitle(children)}
            {...props}>
            {children}
        </Link>
    )
}

function getTitle(node: ReactNode): string | undefined {
    if (typeof node === "string" || typeof node === "number") {
        return String(node)
    }

    if (Array.isArray(node)) {
        return node.map(getTitle).filter(Boolean).join(" ").trim()
    }

    if (React.isValidElement<{ children?: ReactNode }>(node)) {
        return getTitle(node.props.children)
    }

    return undefined
}