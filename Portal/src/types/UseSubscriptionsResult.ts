import type { ExpenseCurrency, Subscription } from "./CoreSwaggerTypes.ts";

export interface UseSubscriptionsResult {
    subscriptions: Subscription[] | null
    createSubscription: (description: string, value: number, currency: ExpenseCurrency, expiration: number) => Promise<Subscription>
    removeSubscription: (subscriptionId: string) => Promise<void>
}