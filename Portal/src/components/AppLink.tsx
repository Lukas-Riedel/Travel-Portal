import { Link, useLocation, type LinkProps } from "react-router-dom"
import type { Navigable } from "../types/Navigable.ts"
import { getPath } from "../utils/navigationUtils.ts"
import type { ReactNode } from "react"
import React from "react"

interface AppLinkProps extends Omit<LinkProps, "to"> {
    to: Navigable
}

export default function AppLink({ to, children, ...props }: AppLinkProps) {
    const { pathname } = useLocation()

    return (
        <Link
            to={getPath(to, pathname)}
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