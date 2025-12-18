import { generateApi } from "swagger-typescript-api"
import * as path from "path"

generateApi({
    name: "CoreSwaggerTypes.ts",
    output: path.resolve(process.cwd(), "./src/types"),
    url: process.env.CORE_BASE_URL + "/swagger/swagger.json",
    generateClient: false,
    routeTypes: false,
    modelTypes: true,
    separateModels: true,
    modelPath: "models"
})