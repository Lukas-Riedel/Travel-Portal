import i18n from "i18next"
import { initReactI18next } from "react-i18next"
import csCommon from "./locales/cs/common.json" with { type: "json" }

i18n
    .use(initReactI18next)
    .init({
        resources: {
            cs: {
                common: csCommon
            }
        },
        lng: "cs",
        fallbackLng: "cs",
        ns: ["common"],
        defaultNS: "common",
        interpolation: {
            escapeValue: false
        }
    })

export default i18n
