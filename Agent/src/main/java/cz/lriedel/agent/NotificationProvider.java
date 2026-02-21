package cz.lriedel.agent;

import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Component;

@Slf4j
@Component
public class NotificationProvider {

    public void sendSystemNotification(String title, String message) {
        String os = System.getProperty("os.name").toLowerCase();

        try {
            if (os.contains("win")) {
                String[] winCmd = {"powershell.exe", "-Command",
                        "Add-Type -AssemblyName System.Windows.Forms; " + "$i = New-Object System.Windows.Forms.NotifyIcon; "
                                + "$i.Icon = [System.Drawing.SystemIcons]::Information; " + "$i.Visible = $true; " + "$i.ShowBalloonTip(15000, '"
                                + title.replace("'", "''") + "', '" + message.replace("'", "''") + "', [System.Windows.Forms.ToolTipIcon]::Info);"};

                Runtime.getRuntime().exec(winCmd);

            }
            else if (os.contains("nix") || os.contains("nux")) {
                String[] linuxCmd = {"notify-send", title, message};

                Runtime.getRuntime().exec(linuxCmd);
            }
        }
        catch (Exception e) {
            log.error(String.format("The notification '%s' could not be sent.", title), e);
        }
    }
}
