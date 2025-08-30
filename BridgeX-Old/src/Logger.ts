export default interface Logger {
    logInfo(text1: string, text2: string): void;
    logError(text1: string, text2: string): void;
}