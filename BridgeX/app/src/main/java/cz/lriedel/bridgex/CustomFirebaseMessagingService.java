package cz.lriedel.bridgex;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.os.Build;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.app.NotificationCompat;

import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import java.lang.reflect.Type;
import java.util.Map;
import java.util.function.Function;
import java.util.stream.Collectors;
import java.util.stream.Stream;

public class CustomFirebaseMessagingService extends FirebaseMessagingService {

    private static final String DEFAULT_CHANNEL_ID = "default_channel";
    private static final String DEFAULT_CHANNEL_NAME = "Default";

    private final Gson gson = new Gson();
    private final Type mapGsonType = new TypeToken<Map<String, Object>>() {}.getType();

    @Nullable
    private DeviceInitializer deviceInitializer;
    @Nullable
    private Map<String, NotificationProcessor> notificationProcessors;

    @Override
    public void onCreate() {
        super.onCreate();

        deviceInitializer = new DeviceInitializer(getApplicationContext(), new AuthenticationService(getApplicationContext()));
        notificationProcessors = Stream.of(new ProcessingEndedNotificationProcessor()).collect(Collectors
                .toMap(ProcessingEndedNotificationProcessor::getSupportedEventName, Function.identity()));
    }

    @Override
    public void onNewToken(@NonNull String token) {
        super.onNewToken(token);

        if (deviceInitializer != null) {
            deviceInitializer.initialize();
        }
    }

    @Override
    public void onMessageReceived(@NonNull RemoteMessage message) {
        super.onMessageReceived(message);

        if (notificationProcessors != null && !message.getData().isEmpty()) {
            Map<String, String> data = message.getData();

            NotificationProcessor notificationProcessor = notificationProcessors.get(data.get("event"));
            if (notificationProcessor != null) {
                Map<String, Object> args = gson.fromJson(data.get("args"), mapGsonType);
                Notification notification = notificationProcessor.process(args);

                if (notification != null) {
                    showNotification(notification);
                }
            }

            // TODO: Send to UI.
        }
    }

    private void showNotification(Notification notification) {
        NotificationManager manager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel channel = new NotificationChannel(DEFAULT_CHANNEL_ID, DEFAULT_CHANNEL_NAME, NotificationManager.IMPORTANCE_DEFAULT);
            manager.createNotificationChannel(channel);
        }

        Intent intent = new Intent(this, MainActivity.class);
        for (Map.Entry<String, Object> intentExtra : notification.intentExtras().entrySet()) {
            intent.putExtra(intentExtra.getKey(), intentExtra.getValue().toString());
        }
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);

        PendingIntent pendingIntent = PendingIntent.getActivity(this, 0, intent,
                PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);

        NotificationCompat.Builder builder = new NotificationCompat.Builder(this, DEFAULT_CHANNEL_ID)
                .setContentTitle(notification.title())
                .setContentText(notification.body())
                .setContentIntent(pendingIntent)
                .setSmallIcon(R.mipmap.ic_launcher)
                .setAutoCancel(true);

        manager.notify((int) System.currentTimeMillis(), builder.build());
    }
}
