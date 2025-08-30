import Logger from "./Logger";

export default class ConsoleLogger implements Logger {
    logInfo(text1: string, text2: string): void {
        console.info(text1 + (text2 === "" ? "" : (" (" + text2 + ")")));
    }

    logError(text1: string, text2: string): void {
        console.error(text1 + (text2 === "" ? "" : (" (" + text2 + ")")));
    }
}