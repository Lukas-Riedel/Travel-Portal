import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import Toast from "react-native-toast-message";
import axios from "axios";
import {
  Button,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";

async function setString(key, value) {
  try {
    await AsyncStorage.setItem(key, value);
  }
  catch (e) {
    console.error(e);
  }
}

async function getString(key) {
  try {
    return await AsyncStorage.getItem(key);
  }
  catch (e) {
    console.error(e);
  }
}

async function synchronize() {
  try {
    const accessToken = (await axios.post(`${baseUrl}/iam`, {
      "apiKey": apiKey
    })).data.accessToken;

    const jobs = (await axios.get(`${baseUrl}/jobs/UpdateFitnessData`, {
      headers: {
        "Authorization": `Bearer ${accessToken}`
      }
    })).data;

    setString("baseUrl", baseUrl);
    setString("apiKey", apiKey);

    Toast.show({
      type: "info",
      text1: "Synchronizace byla zahájena",
      text2: `Ve frontě je ${jobs.length} záznamů`
    });
  }
  catch (e) {
    Toast.show({
      type: "error",
      text1: "Při synchronizaci se vyskytla neočekávaná chyba",
      text2: e.message
    });
  }
}

function App(): React.JSX.Element {
  Text.defaultProps = Text.defaultProps || {};
  Text.defaultProps.style = {fontFamily: "Montserrat"};

  TextInput.defaultProps = TextInput.defaultProps || {};
  TextInput.defaultProps.style = {fontFamily: "Montserrat"};

  return (
    <View style={styles.container}>
      <Text style={{ fontSize: 17, marginVertical: 10 }}>Poslední synchronizace: {lastSync !== null ? lastSync : "N/A"}</Text>

      <Text style={{ marginTop: 10, fontSize: 15 }}>Adresa</Text>
      <TextInput
        style={styles.input}
        placeholder="Service URL"
        defaultValue={baseUrl}
        onChangeText={text => {
          baseUrl = text;
        }}
      />

      <Text style={{ marginTop: 10, fontSize: 15 }}>API klíč</Text>
      <TextInput
        style={styles.input}
        placeholder="API klíč"
        defaultValue={apiKey}
        onChangeText={text => {
          apiKey = text;
        }}
      />

      <View style={{ marginTop: 20 }}>
        <Button
          title="Synchronizovat"
          onPress={() => {
            synchronize()
          }}
        />
      </View>

      <Toast/>
    </View>
  );
}

const styles = StyleSheet.create({
  regularText: {
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
  },
});

let lastSync = null;
let baseUrl = null;
let apiKey = null;

getString("lastSync")
  .then(response => {
    if (response) {
      lastSync = response;
    }
  });

getString("baseUrl")
  .then(response => {
    if (response) {
      baseUrl = response;
    }
  });

getString("apiKey")
  .then(response => {
    if (response) {
      apiKey = response;
    }
  });

export default App;
