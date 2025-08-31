package cz.lriedel.bridgex;

import android.bluetooth.BluetoothAdapter;
import android.content.Context;
import android.os.Build;
import android.provider.Settings;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class DeviceInitializer {

    private static Callback<Void> EMPTY_CALLBACK = new Callback<>() {
        @Override
        public void onResponse(Call<Void> call, Response<Void> response) {
            // Do nothing.
        }

        @Override
        public void onFailure(Call<Void> call, Throwable throwable) {
            // Do nothing.
        }
    };

    private static final String DEVICE_NAME_KEY = "device_name";

    private final String deviceName;
    private final CoreClient coreClient;

    public DeviceInitializer(Context context, AuthenticationService authenticationService) {
        this.deviceName = getPrettyDeviceName(context);
        this.coreClient = CoreClient.create(authenticationService);
    }

    public void initialize(String fcmToken) {
        for (DeviceType deviceType : DeviceType.values()) {
            coreClient.createDevice(new DeviceRequest(deviceType.getValue(), deviceName, fcmToken)).enqueue(EMPTY_CALLBACK);
        }
    }

    private static String getPrettyDeviceName(Context context) {
        String deviceNameSetting = Settings.Global.getString(context.getContentResolver(), DEVICE_NAME_KEY);
        if (deviceNameSetting != null && !deviceNameSetting.isEmpty()) {
            return deviceNameSetting;
        }

        String manufacturer = Build.MANUFACTURER;
        String model = Build.MODEL;
        if (model.toLowerCase().startsWith(manufacturer.toLowerCase())) {
            return capitalize(model);
        } else {
            return capitalize(manufacturer) + " " + model;
        }
    }

    private static String capitalize(String str) {
        if (str == null || str.isEmpty()) return str;
        return str.substring(0, 1).toUpperCase() + str.substring(1);
    }
}
