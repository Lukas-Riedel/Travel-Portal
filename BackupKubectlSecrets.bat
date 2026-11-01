@echo off
cd /d "%~dp0"
powershell.exe -NoProfile -Command "$p=$null; try { $p=Start-Process ssh -ArgumentList '-i private.key -L 6443:localhost:6443 root@lriedel.cz -N' -PassThru; Start-Sleep 2; $yaml=kubectl get secrets --all-namespaces --field-selector='type!=helm.sh/release.v1' -o yaml; if ($LASTEXITCODE -eq 0) { $yaml | Set-Content -Encoding utf8 secrets.yaml } } finally { if ($p) { Stop-Process -Id $p.Id -Force -ErrorAction SilentlyContinue } }" >nul 2>&1
