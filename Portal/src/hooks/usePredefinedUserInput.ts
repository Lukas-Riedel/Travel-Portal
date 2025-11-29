import { useTranslation } from "react-i18next"
import type { UsePredefinedUserInputResult } from "../types/UsePredefinedUserInputResult.ts"
import { useUserInput } from "./useUserInput.tsx"
import type { Airline, Album, Document, Expense, Note, Subscription, Voucher } from "../types/CoreSwaggerTypes.ts"
import { format, fromUnixTime } from "date-fns"
import type { Place } from "../classes/Place.ts"

// TODO: Unify all messages (e.g., its context arguments).
export const usePredefinedUserInput = (): UsePredefinedUserInputResult => {
    const { showConfirmToast, showInputToast, showFormToast } = useUserInput()
    const { t } = useTranslation()

    const showRemoveDocumentToast = (document: Document, removeDocument: () => Promise<void>) =>
        showConfirmToast(
            t("document.prompt.remove.message", { name: document.name }),
            removeDocument,
            t("document.prompt.remove.confirmed"),
            t("document.prompt.remove.failed")
        )

    const showUpdateConfigurationEntryToast = (updateConfigurationEntry: () => Promise<Record<string, any>>) =>
        showConfirmToast(
            t("configuration.prompt.update.message"),
            updateConfigurationEntry,
            t("configuration.prompt.update.confirmed"),
            t("configuration.prompt.update.failed")
        )

    const showRemovePlaceToast = (place: Place, removePlace: () => Promise<void>) =>
        showConfirmToast(
            t("place.prompt.remove.message", { name: place.name }),
            removePlace,
            t("place.prompt.remove.confirmed"),
            t("place.prompt.remove.failed")
        )

    const showRefreshAlbumToast = (refreshAlbum: () => Promise<Album>) =>
        showConfirmToast(
            t("album.prompt.refresh.message"),
            refreshAlbum,
            t("album.prompt.refresh.confirmed"),
            t("album.prompt.refresh.failed")
        )

    const showUpdateAlbumMainPhotoToast = (updateAlbumMainPhoto: () => Promise<Album>) =>
        showConfirmToast(
            t("album.prompt.update.cover.message"),
            updateAlbumMainPhoto,
            t("album.prompt.update.cover.confirmed"),
            t("album.prompt.update.cover.failed")
        )

    const showCreateExpenseToast = (subscriptions: Subscription[], vouchers: Voucher[], createExpense: (subscriptionId?: string) => Promise<Expense>, updateVoucherValue: (voucherId: string, value: number) => Promise<Voucher>, removeVoucher: (voucherId: string) => Promise<void>) =>
        showFormToast(
            t("expense.prompt.create.message"),
            [
                {
                    type: "select",
                    required: false,
                    label: t("expense.prompt.create.label.subscription"),
                    options: subscriptions.map(subscription => ({
                        id: subscription.id,
                        name: t("expense.subscription", { description: subscription.description, expiration: format(fromUnixTime(subscription.expiration), t("general.format.date")) })
                    }))
                },
                {
                    type: "select",
                    required: false,
                    label: t("expense.prompt.create.label.voucher"),
                    options: vouchers.map(voucher => ({
                        id: voucher.id,
                        name: voucher.expiration
                            ? t("expense.voucher.expirable", { issuer: voucher.issuer, value: voucher.value, currency: voucher.currency, expiration: format(fromUnixTime(voucher.expiration), t("general.format.date")) })
                            : t("expense.voucher.nonexpirable", { issuer: voucher.issuer, value: voucher.value, currency: voucher.currency }),
                    }))
                }
            ] as const,
            async (subscriptionId, voucherId) => createExpense(subscriptionId)
                .then(async expense => {
                    if (voucherId) {
                        const voucher = vouchers.find(voucher => voucher.id === voucherId)
                        if (voucher.currency !== expense.currency) {
                            return Promise.reject("The voucher currency must match the expense currency.")
                        }

                        if (voucher.value <= expense.value) {
                            await removeVoucher(voucherId)
                        }
                        else {
                            await updateVoucherValue(voucherId, voucher.value - expense.value)
                        }
                    }

                    return Promise.resolve(expense)
                }),
            t("expense.prompt.create.confirmed"),
            t("expense.prompt.create.failed")
        )

    const showUpdateExpenseValueToast = (expense: Expense, currencies: string[], updateExpenseValue: (value: number, currency: string) => Promise<Expense>) =>
        showFormToast(
            t("expense.prompt.update.value.message"),
            [
                {
                    type: "number",
                    required: true,
                    label: t("expense.prompt.update.value.label.value"),
                    defaultValue: expense.value,
                    min: 0
                },
                {
                    type: "select",
                    required: true,
                    label: t("expense.prompt.update.value.label.currency"),
                    defaultValue: expense.currency,
                    options: currencies.map(currency => ({
                        id: currency,
                        name: currency
                    }))
                }
            ] as const,
            updateExpenseValue,
            t("expense.prompt.update.value.confirmed"),
            t("expense.prompt.update.value.failed")
        )

    const showUpdateExpenseDescriptionToast = (expense: Expense, updateExpenseDescription: (description: string) => Promise<Expense>) =>
        showInputToast(
            t("expense.prompt.update.description.message"),
            updateExpenseDescription,
            t("expense.prompt.update.description.confirmed"),
            t("expense.prompt.update.description.failed"),
            expense.description
        )

    const showRemoveExpenseToast = (removeExpense: () => Promise<void>) =>
        showConfirmToast(
            t("expense.prompt.remove.message"),
            removeExpense,
            t("expense.prompt.remove.confirmed"),
            t("expense.prompt.remove.failed")
        )

    const showCreateNoteToast = (createNote: (content: string) => Promise<Note>) =>
        showInputToast(
            t("note.prompt.create.message"),
            createNote,
            t("note.prompt.create.confirmed"),
            t("note.prompt.create.failed")
        )

    const showRemoveNoteToast = (removeNote: () => Promise<void>) =>
        showConfirmToast(
            t("note.prompt.remove.message"),
            removeNote,
            t("note.prompt.remove.confirmed"),
            t("note.prompt.remove.failed")
        )

    const showCreateAirlineToast = (createAirline: (name: string) => Promise<Airline>) =>
        showInputToast(
            t("airline.prompt.create.message"),
            createAirline,
            t("airline.prompt.create.confirmed"),
            t("airline.prompt.create.failed")
        )

    const showUpdateAirlineToast = (airline: Airline, updateAirlineName: (name: string) => Promise<Airline>,
        updateAirlineLogo: (logo: string) => Promise<Airline>, removeAirlineCode: (code: string) => Promise<void>) =>
        showFormToast(
            t("airline.prompt.update.message"),
            [
                {
                    type: "text",
                    required: true,
                    label: t("airline.prompt.update.label.name"),
                    defaultValue: airline.name,
                },
                {
                    type: "text",
                    required: false,
                    label: t("airline.prompt.update.label.logo"),
                    defaultValue: airline.logo
                },
                {
                    type: "select",
                    required: false,
                    label: t("airline.prompt.update.label.codes"),
                    defaultValue: airline.codes,
                    multiple: true,
                    options: airline.codes.map(code => ({ id: code, name: code }))
                }
            ] as const,
            async (name, logo, codes) => {
                if (airline.name !== name) {
                    await updateAirlineName(name)
                }

                if (airline.logo !== logo) {
                    await updateAirlineLogo(logo)
                }

                await Promise.all(airline.codes.filter(code => !codes.includes(code)).map(code => removeAirlineCode(code)))
            },
            t("airline.prompt.update.confirmed"),
            t("airline.prompt.update.failed")
        )

    const showRemoveAirlineToast = (airline: Airline, removeAirline: () => Promise<void>) =>
        showConfirmToast(
            t("airline.prompt.remove.message", { name: airline.name }),
            removeAirline,
            t("airline.prompt.remove.confirmed"),
            t("airline.prompt.remove.failed")
        )

    return {
        showRemoveDocumentToast,
        showUpdateConfigurationEntryToast,
        showRemovePlaceToast,
        showRefreshAlbumToast,
        showUpdateAlbumMainPhotoToast,
        showCreateExpenseToast,
        showUpdateExpenseValueToast,
        showUpdateExpenseDescriptionToast,
        showRemoveExpenseToast,
        showCreateNoteToast,
        showRemoveNoteToast,
        showCreateAirlineToast,
        showUpdateAirlineToast,
        showRemoveAirlineToast
    }
}