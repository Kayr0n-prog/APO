# Chamado pelo Agendador de Tarefas. Lê a chave criptografada, roda a rotina
# e mostra uma notificação. A chave só existe na memória deste processo.
$ErrorActionPreference = "Stop"
$Pasta = Split-Path -Parent $MyInvocation.MyCommand.Path
$Raiz = Split-Path -Parent $Pasta
$Saida = Join-Path $env:USERPROFILE "Videos\TikTokAuto"
$LogDir = Join-Path $Saida "logs"
New-Item -ItemType Directory -Force -Path $LogDir | Out-Null
$LogAg = Join-Path $LogDir "agendador.log"
function Log($m) { Add-Content -Path $LogAg -Value ("{0} {1}" -f (Get-Date -Format s), $m) -Encoding UTF8 }

function Avisar($titulo, $texto) {
    try {
        Add-Type -AssemblyName System.Windows.Forms
        $n = New-Object System.Windows.Forms.NotifyIcon
        $n.Icon = [System.Drawing.SystemIcons]::Information
        $n.BalloonTipTitle = $titulo
        $n.BalloonTipText = $texto
        $n.Visible = $true
        $n.ShowBalloonTip(15000)
        Start-Sleep -Seconds 16
        $n.Dispose()
    } catch { }
}

try {
    $env:Path = [Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [Environment]::GetEnvironmentVariable("Path", "User")
    $arq = Join-Path $env:LOCALAPPDATA "TikTokAuto\pixabay_key.dpapi"
    if (-not (Test-Path $arq)) { throw "chave do Pixabay não configurada; rode instalar.ps1" }
    $seg = Get-Content $arq | ConvertTo-SecureString
    $ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($seg)
    try { $env:PIXABAY_API_KEY = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr) }
    $env:PYTHONIOENCODING = "utf-8"

    $py = if (Get-Command py -ErrorAction SilentlyContinue) { @("py", "-3") } else { @("python") }
    Log "início"
    $exe = $py[0]
    $args_ = @()
    if ($py.Length -gt 1) { $args_ += $py[1] }
    $args_ += @((Join-Path $Raiz "run.py"), "--saida", $Saida)
    & $exe @args_ *>> (Join-Path $LogDir "execucao.log")
    $codigo = $LASTEXITCODE
    Remove-Item Env:\PIXABAY_API_KEY -ErrorAction SilentlyContinue
    Log "fim (código $codigo)"
    $hoje = Get-Date -Format "yyyy-MM-dd"
    switch ($codigo) {
        0 { Avisar "TikTokAuto" "Os 3 vídeos de hoje estão prontos em Vídeos\TikTokAuto\$hoje" }
        2 { Avisar "TikTokAuto" "Alguns vídeos de hoje não foram gerados. Veja PROBLEMAS.txt em Vídeos\TikTokAuto\$hoje" }
        3 { Avisar "TikTokAuto" "Problema com a chave do Pixabay. Rode instalar.ps1 de novo." }
        default { Avisar "TikTokAuto" "A rotina falhou. Veja Vídeos\TikTokAuto\logs" }
    }
    exit $codigo
} catch {
    Remove-Item Env:\PIXABAY_API_KEY -ErrorAction SilentlyContinue
    Log ("erro: " + $_.Exception.Message)
    Avisar "TikTokAuto" ("Falha: " + $_.Exception.Message)
    exit 1
}
