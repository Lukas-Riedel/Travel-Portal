package cz.lriedel.agent;

import lombok.extern.slf4j.Slf4j;
import org.apache.commons.lang3.SystemUtils;
import org.springframework.stereotype.Component;

@Slf4j
@Component
// TODO: Reimplement via FCM to all user's devices.
public class NotificationProvider {

    public void sendSystemNotification(String title, String message) {
        try {
            if (SystemUtils.IS_OS_WINDOWS) {
                String[] winCmd = {"powershell.exe", "-Command",
                        "Add-Type -AssemblyName System.Windows.Forms; " + "$i = New-Object System.Windows.Forms.NotifyIcon; "
                                + "$i.Icon = [System.Drawing.SystemIcons]::Information; " + "$i.Visible = $true; " + "$i.ShowBalloonTip(15000, '"
                                + title.replace("'", "''") + "', '" + message.replace("'", "''") + "', [System.Windows.Forms.ToolTipIcon]::Info);"};

                Runtime.getRuntime().exec(winCmd);

            }
            else {
                String[] linuxCmd = {"notify-send", title, message};

                Runtime.getRuntime().exec(linuxCmd);
            }
        }
        catch (Exception e) {
            log.error(String.format("The notification '%s' could not be sent.", title), e);
        }
    }
}
