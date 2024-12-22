import React, { useEffect, useState } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import Toast from "react-native-toast-message";
import axios from "axios";
import { format } from "date-fns";
import {
    Button,
    StyleSheet,
    Text,
    TextInput,
    View,
} from "react-native";
import {
    AggregateResultRecordType,
    RecordType,
    initialize,
    requestPermission,
    aggregateRecord,
    readRecords
} from "react-native-health-connect";

const setValue = async (key: string, value: string): Promise<void> => {
    try {
        await AsyncStorage.setItem(key, value);
    }
    catch (e) {
        console.error(e);
    }
};

const getValue = async (key: string): Promise<string | null> => {
    try {
        return await AsyncStorage.getItem(key);
    }
    catch (e) {
        console.error(e);
    }
    return null;
};

const askForPermissions = async (): Promise<void> => {
    await initialize();
    await requestPermission([
        { accessType: "read", recordType: "Steps" },
        { accessType: "read", recordType: "Distance" },
        { accessType: "read", recordType: "TotalCaloriesBurned" }
    ]);
};

const getFitnessFitnessData = async(recordType: AggregateResultRecordType, start: number, end: number): Promise<any> => {
    return await aggregateRecord({
        recordType: recordType,
        timeRangeFilter: {
            operator: "between",
            startTime: new Date(start * 1000).toISOString(),
            endTime: new Date(end * 1000).toISOString()
        }
    });
};

const getFitnessData = async(recordType: RecordType, start: number, end: number): Promise<any> => {
    return await readRecords(recordType, {
        timeRangeFilter: {
            operator: "between",
            startTime: new Date(start * 1000).toISOString(),
            endTime: new Date(end * 1000).toISOString()
        }
    });
};

const getTotalSteps = async (start: number, end: number): Promise<number> => {
    return (await getFitnessFitnessData("Steps", start, end)).COUNT_TOTAL;
};

const getTotalDistance = async (start: number, end: number): Promise<number> => {
    return (await getFitnessFitnessData("Distance", start, end)).DISTANCE.inMeters;
};

const getTotalCalories = async (start: number, end: number): Promise<number> => {
    return (await getFitnessFitnessData("TotalCaloriesBurned", start, end)).ENERGY_TOTAL.inKilocalories;
};

const getTotalTimeInMotion = async (start: number, end: number): Promise<number> => {
    let totalTimeInMotion = 0;

    const stepRecords = (await getFitnessData("Steps", start, end)).records;
    for (const stepRecord of stepRecords) {
        totalTimeInMotion += (Number(new Date(stepRecord.endTime)) - Number(new Date(stepRecord.startTime)))
    }

    return Math.round(totalTimeInMotion / (60 * 1000));
};

const updateFitness = async(accessToken: string, start: number, end: number): Promise<void> => {
    await axios.put(`${baseUrl}/fitness/${start}`, {
        "steps": await getTotalSteps(start, end),
        "distance": await getTotalDistance(start, end),
        "minutes": await getTotalTimeInMotion(start, end),
        "calories": await getTotalCalories(start, end)
    }, {
        headers: {
            "Authorization": `Bearer ${accessToken}`
        }
    });
}

const synchronize = async() : Promise<void> => {
    try {
        const accessToken = (await axios.post(`${baseUrl}/iam`, {
            "apiKey": apiKey
        })).data.accessToken;

        const configuration = (await axios.get(`${baseUrl}/configuration?levels=public`, {
            headers: {
                "Authorization": `Bearer ${accessToken}`
            }
        })).data;

        setValue("baseUrl", baseUrl!);
        setValue("apiKey", apiKey!);

        const jobs = (await axios.get(`${baseUrl}/jobs?action=UpdateFitnessData`, {
            headers: {
                "Authorization": `Bearer ${accessToken}`
            }
        })).data;

        Toast.show({
            type: "info",
            text1: "Synchronizace byla zahájena",
            text2: `Ve frontě je ${jobs.length} záznamů`
        });

        for (const job of jobs) {
            try {
                await updateFitness(accessToken, job.args.start, job.args.start + configuration.fitnessRecordDuration);
            }
            catch (e) {
                console.error(e);
            }
            finally {
                await axios.delete(`${baseUrl}/jobs/${job.id}`, {
                    headers: {
                        "Authorization": `Bearer ${accessToken}`
                    }
                });
            }
        }

        Toast.show({
            type: "info",
            text1: "Synchronizace byla úspěšně dokončena"
        });

        setValue("lastSync", format(new Date(), "dd.MM.yyyy HH:mm"));
    }
    catch (e) {
        Toast.show({
            type: "error",
            text1: "Při synchronizaci se vyskytla neočekávaná chyba",
            text2: (e as Error).message
        });
    }
}

const App = (): React.JSX.Element => {
    const [lastSync, setLastSync] = useState("N/A");

    const fetchAndSetLastSync = async () => {
        const lastSyncValue = await getValue("lastSync");
        setLastSync(lastSyncValue !== null ? lastSyncValue : "N/A");
    }

    const synchronizeAndSetLastSync = async () => {
        synchronize().then(fetchAndSetLastSync);
    }

    useEffect(() => {
        fetchAndSetLastSync();
    }, []);

    return (
        <View style={styles.container}>
            <Text style={[styles.text, { marginVertical: 10 }]}>Poslední synchronizace</Text>
            <Text style={styles.text}>{lastSync}</Text>

            <Text style={[styles.text, { marginTop: 30 }]}>Adresa</Text>
            <TextInput
                style={[styles.text, styles.input]}
                placeholder="Adresa"
                defaultValue={baseUrl ?? ""}
                onChangeText={text => {
                    baseUrl = text;
                }}
            />

            <Text style={[styles.text, { marginTop: 10 }]}>API klíč</Text>
            <TextInput
                style={[styles.text, styles.input]}
                placeholder="API klíč"
                defaultValue={apiKey ?? ""}
                onChangeText={text => {
                    apiKey = text;
                }}
            />

            <View style={{ marginTop: 20 }}>
                <Button
                    title="Synchronizovat"
                    onPress={synchronizeAndSetLastSync}
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

let baseUrl: string | null = null;
let apiKey: string | null = null;

getValue("baseUrl")
    .then(response => {
        if (response) {
            baseUrl = response;
        }
    });

getValue("apiKey")
    .then(response => {
        if (response) {
            apiKey = response;
        }
    });

askForPermissions();

export default App;
