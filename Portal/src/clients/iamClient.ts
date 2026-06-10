import axios from "axios"
import type { AxiosInstance, AxiosResponse } from "axios"
import type { IamResponse } from "../types/IamResponse.ts"
import axiosRetry from "axios-retry"

export const getIamResponseWithCredentials = async (username: string, password: string): Promise<IamResponse> =>
    iamClient.post<IamResponse>("token",
        {
            username,
            password
        }
    ).then(extractIamResponse)

export const getIamResponseWithRefresh = async (refreshToken: string): Promise<IamResponse> =>
    iamClient.post<IamResponse>("token",
        {
            refreshToken
        }
    ).then(extractIamResponse)

const iamClient: AxiosInstance = axios.create({
    baseURL: window.env?.VITE_IAM_BASE_URL || import.meta.env.VITE_IAM_BASE_URL,
    headers: {
        "Content-Type": "application/json",
        "Request-Origin": window.env?.VITE_APP_NAME || import.meta.env.VITE_APP_NAME
    }
})

const extractIamResponse = (response: AxiosResponse<IamResponse>): IamResponse => response.data

const axiosGridRetryCondition = (error: any) => {
    if (!error.response) {
        return true
    }
    if (error.response.status >= 500 && error.response.status <= 599) {
        return true
    }
    return false
}

axiosRetry(iamClient, {
    retries: 3,
    retryDelay: axiosRetry.exponentialDelay,
    retryCondition: axiosGridRetryCondition
})