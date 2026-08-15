package cz.lriedel.bridgex.device

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import com.google.android.gms.location.LocationResult

class PassiveLocationReceiver : BroadcastReceiver() {

    override fun onReceive(context: Context, intent: Intent) {
        if (!LocationResult.hasResult(intent)) {
            return
        }

        val deviceForegroundServiceRunner = DeviceForegroundServiceRunner(context)
        deviceForegroundServiceRunner.execute()
    }
}