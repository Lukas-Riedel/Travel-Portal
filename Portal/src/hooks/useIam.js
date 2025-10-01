import axios from "axios"

export function useIam() {
    async function sendRequest(data) {
        try {
            const response = await axios({
                method: "POST",
                url: import.meta.env.VITE_IAM_BASE_URL + "/token",
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

    async function getIamResponseWithCredentials(username, password) {
        return sendRequest(
            {
                username: username,
                password: password
            })
    }

    async function getIamResponseWithRefresh(refreshToken) {
        return sendRequest(
            {
                refreshToken: refreshToken
            })
    }

    return {
        getIamResponseWithCredentials,
        getIamResponseWithRefresh
    }
}