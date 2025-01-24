import React, { useEffect, useState } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import Toast from "react-native-toast-message";
import axios from "axios";
import { format } from "date-fns";
import {
    Button,
    PermissionsAndroid,
    StyleSheet,
    Text,
    TextInput,
    View,
} from "react-native";
import {
    initialize,
    requestPermission,
} from "react-native-health-connect";
import BackgroundFetch from "react-native-background-fetch";
import Synchronizer from "./src/Synchronizer";
import ToastLogger from "./src/ToastLogger";
import ConsoleLogger from "./src/ConsoleLogger";

const askForPermissions = async (): Promise<void> => {
    await initialize();
    await requestPermission([
        { accessType: "read", recordType: "Steps" },
        { accessType: "read", recordType: "Distance" },
        { accessType: "read", recordType: "TotalCaloriesBurned" }
    ]);
    await PermissionsAndroid.request("android.permission.health.READ_HEALTH_DATA_IN_BACKGROUND" as any);
};

const App = (): React.JSX.Element => {
    const [baseUrl, setBaseUrl] = useState("");
    const [apiKey, setApiKey] = useState("");
    const [lastSync, setLastSync] = useState(new Date(0));
    
    const toastLogger = new ToastLogger();

    const foregroundSynchronizer = new Synchronizer(toastLogger);
    const backgroundSynchronizer = new Synchronizer(new ConsoleLogger());

    const doSynchronizeAndSetLastSync = async () => {
        await foregroundSynchronizer.doSynchronize();
        AsyncStorage.getItem("lastSync").then(value => setLastSync(new Date(Number(value ?? 0))));
    };

    useEffect(() => {
        AsyncStorage.getItem("baseUrl").then(value => setBaseUrl(value ?? ""));
        AsyncStorage.getItem("apiKey").then(value => setApiKey(value ?? ""));
        AsyncStorage.getItem("lastSync").then(value => setLastSync(new Date(Number(value ?? 0))));

        BackgroundFetch.configure({
            minimumFetchInterval: 15,
            enableHeadless: true,
            forceAlarmManager: false,
            stopOnTerminate: false,
            startOnBoot: true,
            requiredNetworkType: BackgroundFetch.NETWORK_TYPE_UNMETERED,
            requiresBatteryNotLow: true
        }, async (taskId) => {
            await backgroundSynchronizer.synchronize();
            AsyncStorage.getItem("lastSync").then(value => setLastSync(new Date(Number(value ?? 0))));
            BackgroundFetch.finish(taskId);
        }, (e) => {
            toastLogger.logError("There was an error processing the item", e);
        });
    }, []);

    return (
        <View style={styles.container}>
            <Text style={[styles.text, { marginVertical: 10 }]}>Last synchronization</Text>
            <Text style={styles.text}>{lastSync.getTime() > 0 ? format(lastSync, "dd.MM.yyyy HH:mm") : "N/A"}</Text>

            <Text style={[styles.text, { marginTop: 30 }]}>Service URL</Text>
            <TextInput
                style={[styles.text, styles.input]}
                placeholder="Service URL"
                defaultValue={baseUrl}
                onChangeText={text => {
                    setBaseUrl(text);
                    AsyncStorage.setItem("baseUrl", text);
                }}
            />

            <Text style={[styles.text, { marginTop: 10 }]}>API key</Text>
            <TextInput
                style={[styles.text, styles.input]}
                placeholder="API key"
                defaultValue={apiKey}
                onChangeText={text => {
                    setApiKey(text);
                    AsyncStorage.setItem("apiKey", text);
                }}
            />

            <View style={{ marginTop: 20 }}>
                <Button
                    title="Synchronize"
                    onPress={doSynchronizeAndSetLastSync}
                />
            </View>

            <Toast/>
        </View>
    );
}

const styles = StyleSheet.create({
    text: {
        fontFamily: "Montserrat",
        fontSize: 16,
    },
    container: {
        backgroundColor: "#fff",
        alignItems: "center",
        justifyContent: "center",
        height: "100%",
        width: "100%",
        textAlign: "center",
        padding: 50
    },
    input: {
        height: 50,
        marginVertical: 7,
        borderWidth: 1,
        borderRadius: 4,
        padding: 10,
        width: 350,
        fontSize: 17
    }
});

askForPermissions();

export default App;
