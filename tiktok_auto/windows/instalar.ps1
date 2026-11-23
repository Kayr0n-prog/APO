# Instala a rotina diária de vídeos no Agendador de Tarefas do Windows.
# Uso (PowerShell): powershell -ExecutionPolicy Bypass -File instalar.ps1 [-Horario 08:00] [-TestarAgora]
param(
    [string]$Horario = "08:00",
    [switch]$TestarAgora
)
$ErrorActionPreference = "Stop"
$NomeTarefa = "TikTokAuto - videos diarios"
$Pasta = Split-Path -Parent $MyInvocation.MyCommand.Path
$Executar = Join-Path $Pasta "executar.ps1"
$DadosApp = Join-Path $env:LOCALAPPDATA "TikTokAuto"
$ArqChave = Join-Path $DadosApp "pixabay_key.dpapi"
New-Item -ItemType Directory -Force -Path $DadosApp | Out-Null

function Tem($cmd) { return [bool](Get-Command $cmd -ErrorAction SilentlyContinue) }
function AtualizarPath {
    $env:Path = [Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [Environment]::GetEnvironmentVariable("Path", "User")
}

Write-Host "1/4 Verificando Python..."
if (-not ((Tem "py") -or (Tem "python"))) {
    if (-not (Tem "winget")) { throw "Instale o Python 3 (python.org) e rode este instalador de novo." }
    winget install -e --id Python.Python.3.12 --accept-source-agreements --accept-package-agreements
    AtualizarPath
}

Write-Host "2/4 Verificando ffmpeg..."
if (-not (Tem "ffmpeg")) {
    if (-not (Tem "winget")) { throw "Instale o ffmpeg (gyan.dev) e coloque no PATH." }
    winget install -e --id Gyan.FFmpeg --accept-source-agreements --accept-package-agreements
    AtualizarPath
    if (-not (Tem "ffmpeg")) { Write-Warning "ffmpeg instalado; se o teste falhar, feche e abra o PowerShell e rode de novo." }
}

Write-Host "3/4 Chave do Pixabay"
if ((Test-Path $ArqChave) -and ((Read-Host "Já existe uma chave salva. Trocar? (s/N)") -ne "s")) {
    Write-Host "   mantendo a chave atual."
} else {
    $seg = Read-Host "Cole a sua chave da API do Pixabay (não aparece na tela)" -AsSecureString
    # Criptografada com DPAPI: só o seu usuário, neste computador, consegue ler.
    $seg | ConvertFrom-SecureString | Set-Content -Path $ArqChave -Encoding ASCII
    Write-Host "   chave salva de forma criptografada em $ArqChave"
}

Write-Host "4/4 Registrando a tarefa '$NomeTarefa'..."
$acao = New-ScheduledTaskAction -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$Executar`""
$diario = New-ScheduledTaskTrigger -Daily -At $Horario
$logon = New-ScheduledTaskTrigger -AtLogOn -User "$env:USERDOMAIN\$env:USERNAME"
$logon.Delay = "PT5M"
$config = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
    -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 15) -ExecutionTimeLimit (New-TimeSpan -Hours 3) `
    -MultipleInstances IgnoreNew
$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName $NomeTarefa -Action $acao -Trigger @($diario, $logon) -Settings $config `
    -Principal $principal -Description "Gera 3 vídeos 9:16 por dia com material do Pixabay (tiktok_auto)." -Force | Out-Null
Write-Host "   tarefa registrada: todo dia às $Horario e 5 min após o login (se o PC estava desligado, roda quando ligar)."

if ($TestarAgora) {
    Write-Host "Executando agora para testar (alguns minutos)..."
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $Executar
    Write-Host "Código de saída: $LASTEXITCODE (0 = tudo certo)"
}
Write-Host "Pronto. Os vídeos ficam em $env:USERPROFILE\Videos\TikTokAuto\<data>"
