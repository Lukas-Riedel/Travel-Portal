import { optimize } from "svgo"

export function getSafeSvgString(svgString: string, prefix: string): string {
    const result = optimize(svgString, {
        plugins: [
            {
                name: "prefixIds",
                params: {
                    prefix,
                    delim: "-",
                    prefixIds: true,
                    prefixClassNames: true
                }
            }
        ]
    })

    if ("data" in result) {
        return result.data
    }

    return svgString
}