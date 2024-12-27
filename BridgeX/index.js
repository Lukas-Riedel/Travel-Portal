import {AppRegistry} from 'react-native';
import BackgroundFetch from "react-native-background-fetch";
import App from './App';
import {name as appName} from './app.json';
import Synchronizer from "./src/Synchronizer";
import ConsoleLogger from './src/ConsoleLogger';

const synchronizationHeadlessTask = async (event) => {
    if (event.timeout) {
        BackgroundFetch.finish(event.taskId);
        return;
    }
    
    const synchronizer = new Synchronizer(new ConsoleLogger());
    await synchronizer.synchronize();
    BackgroundFetch.finish(event.taskId);
};

BackgroundFetch.registerHeadlessTask(synchronizationHeadlessTask);

AppRegistry.registerComponent(appName, () => App);
