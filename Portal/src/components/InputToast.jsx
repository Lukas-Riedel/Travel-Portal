import showFormToast from "./FormToast"

export default function showInputToast(title, placeholder, success, error, onSubmitted) {
    return showFormToast(
        title,
        [
            { placeholder: placeholder, value: placeholder, required: true }
        ],
        success,
        error,
        onSubmitted
    )
}
