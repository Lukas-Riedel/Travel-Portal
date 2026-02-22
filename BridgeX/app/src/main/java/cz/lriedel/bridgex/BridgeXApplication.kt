package cz.lriedel.bridgex

import android.app.Application
import com.google.firebase.FirebaseApp
import com.google.firebase.FirebaseOptions

class BridgeXApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        
        if (FirebaseApp.getApps(this).isEmpty()) {
            val options = FirebaseOptions.Builder()
                .setApiKey(BuildConfig.FIREBASE_API_KEY)
                .setApplicationId(BuildConfig.FIREBASE_APP_ID)
                .setProjectId(BuildConfig.FIREBASE_PROJECT_ID)
                .setStorageBucket(BuildConfig.FIREBASE_STORAGE_BUCKET)
                .setGcmSenderId(BuildConfig.FIREBASE_MESSAGING_SENDER_ID)
                .build()
            FirebaseApp.initializeApp(this, options)
        }
    }
}
