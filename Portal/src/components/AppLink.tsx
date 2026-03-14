import { Link, useLocation, type LinkProps } from "react-router-dom"

const PLANS_PAGE_PREFIX = "/plan"

export default function AppLink({ to, children, ...props }: LinkProps) {
    const { pathname } = useLocation()
    const prefix = pathname.startsWith(PLANS_PAGE_PREFIX) ? PLANS_PAGE_PREFIX : ""
    
    return (
        <Link to={`${prefix}${to}`} {...props}>
            {children}
        </Link>
    )
}