import axios from "axios"

export const getIamResponseWithCredentials = async (username, password) =>
    iamClient.post("token",
        {
            username,
            password
        }
    ).then(extractData)

export const getIamResponseWithRefresh = async (refreshToken) =>
    iamClient.post("token",
        {
            refreshToken
        }
    ).then(extractData)

const iamClient = axios.create({
    baseURL: import.meta.env.VITE_IAM_BASE_URL,
    headers: {
        "Content-Type": "application/json",
    }
})

const extractData = response => response.data