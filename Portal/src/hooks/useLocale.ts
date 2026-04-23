import { useTranslation } from "react-i18next"
import { cs, enUS, de } from "date-fns/locale"
import type { UseLocaleResult } from "../types/UseLocaleResult.ts"

const locales = {
    cs: cs
}

export const useLocale = (): UseLocaleResult => {
    const { i18n } = useTranslation()
    return locales[i18n.language?.split("-")[0]] || enUS
};