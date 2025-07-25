import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useLabel = labelId => {
    const { getLabel, updateLabelName } = useApi()
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getLabel", labelId],
        queryFn: () => getLabel(labelId),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12,
    })

    const setLabel = label => queryClient.setQueryData(["getLabel", labelId], label)

    return {
        // TODO: Map to Label object
        label: query.data,
        updateLabelName: name => updateLabelName(labelId, name).then(setLabel)
    }
}