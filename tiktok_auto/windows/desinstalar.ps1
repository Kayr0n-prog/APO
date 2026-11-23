# Remove a tarefa agendada e a chave salva. Os vídeos já gerados não são apagados.
Unregister-ScheduledTask -TaskName "TikTokAuto - videos diarios" -Confirm:$false -ErrorAction SilentlyContinue
Remove-Item (Join-Path $env:LOCALAPPDATA "TikTokAuto\pixabay_key.dpapi") -ErrorAction SilentlyContinue
Write-Host "Tarefa e chave removidas."
