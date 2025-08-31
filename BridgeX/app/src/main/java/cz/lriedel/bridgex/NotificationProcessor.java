package cz.lriedel.bridgex;

import androidx.annotation.Nullable;

import java.util.Map;

public interface NotificationProcessor {

     @Nullable
     Notification process(Map<String, Object> args);

     String getSupportedEventName();
}
