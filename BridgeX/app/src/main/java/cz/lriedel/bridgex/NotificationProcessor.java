package cz.lriedel.bridgex;

import androidx.annotation.Nullable;

import java.util.Map;

// TODO: Rename to NotificationFactory
public interface NotificationProcessor {

     @Nullable
     Notification process(Map<String, Object> args);

     String getSupportedEventName();
}
