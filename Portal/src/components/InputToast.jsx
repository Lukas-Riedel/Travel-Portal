import showFormToast from "./FormToast"

export default function showInputToast(title, value, success, error, onSubmitted) {
    return showFormToast(
        title,
        [{ value: value, required: true }],
        success,
        error,
        onSubmitted
    )
}
