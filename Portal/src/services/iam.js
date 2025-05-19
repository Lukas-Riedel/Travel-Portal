import axios from "axios"

export async function getAccessTokenForUser(username, password) {
    return sendRequest(
        {
            username: username,
            password: password
        })
}

export async function getAccessTokenForApiKey(apiKey) {
    return sendRequest(
        {
            apiKey: apiKey
        })
}

async function sendRequest(data) {
    try {
        const response = await axios({
            method: "POST",
            url: import.meta.env.VITE_IAM_BASE_URL,
            data: data,
            headers: {
                "Content-Type": "application/json",
            },
        })

        return response.data
    }
    catch (e) {
        return Promise.reject(e)
    }
}