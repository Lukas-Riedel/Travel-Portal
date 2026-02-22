import { generateApi } from "swagger-typescript-api"
import * as path from "path"

generateApi({
    fileName: "CoreSwaggerTypes.ts",
    output: path.resolve(process.cwd(), "./src/types"),
    url: process.env.CORE_BASE_URL + "/swagger/swagger.json",
    generateClient: false,
    routeTypes: false,
    modelTypes: true,
    separateModels: true,
    modelPath: "models",
    prettier: false
}).then(() => {
    process.exit(0)
}).catch(e => {
    console.error(e)
    process.exit(1)
})