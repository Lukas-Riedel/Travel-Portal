import Logger from "./Logger";
import Toast from "react-native-toast-message";

export default class ToastLogger implements Logger {
    logInfo(text1: string, text2: string): void {
        Toast.show({
            type: "info",
            text1: text1,
            text2: text2
        });
    }

    logError(text1: string, text2: string): void {
        Toast.show({
            type: "error",
            text1: text1,
            text2: text2
        });
    }
}