<#
.SYNOPSIS
    Copy the local db_tk_final MySQL database (schema + real data + routines/triggers/views)
    into the Railway MySQL service.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts\import-db-to-railway.ps1 -RailwayUrl "mysql://root:PASSWORD@HOST.proxy.rlwy.net:PORT/railway"

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts\import-db-to-railway.ps1 -ExportOnly
#>
param(
    [string]$RailwayUrl,
    [string]$MysqlBin = 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin',
    [string]$EnvFile = (Join-Path (Split-Path $PSScriptRoot -Parent) '.env'),
    [switch]$ExportOnly,
    [switch]$Force
)

$ErrorActionPreference = 'Stop'

function Read-DotEnv([string]$Path) {
    $values = @{}
    foreach ($line in [IO.File]::ReadAllLines($Path)) {
        if ($line -match '^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$') {
            $values[$Matches[1]] = $Matches[2].Trim().Trim('"').Trim("'")
        }
    }
    return $values
}

$mysqldump = Join-Path $MysqlBin 'mysqldump.exe'
$mysql = Join-Path $MysqlBin 'mysql.exe'
foreach ($bin in @($mysqldump, $mysql)) {
    if (-not (Test-Path $bin)) { throw "MySQL client not found: $bin (set -MysqlBin)" }
}

$envValues = Read-DotEnv $EnvFile
$localHost = if ($envValues['DB_HOST']) { $envValues['DB_HOST'] } else { '127.0.0.1' }
$localPort = if ($envValues['DB_PORT']) { $envValues['DB_PORT'] } else { '3306' }
$localDb = if ($envValues['DB_DATABASE']) { $envValues['DB_DATABASE'] } else { 'db_tk_final' }
$localUser = if ($envValues['DB_USERNAME']) { $envValues['DB_USERNAME'] } else { 'root' }
$localPassword = [string]$envValues['DB_PASSWORD']

$dumpFile = Join-Path ([IO.Path]::GetTempPath()) 'sinar_connect_railway.sql'

try {
    # 1. Export local database
    Write-Host "[1/4] Export database lokal '$localDb' dari $($localHost):$localPort ..." -ForegroundColor Cyan
    $env:MYSQL_PWD = $localPassword
    & $mysqldump "--host=$localHost" "--port=$localPort" "--user=$localUser" `
        --single-transaction --routines --triggers --events --no-tablespaces `
        --set-gtid-purged=OFF --default-character-set=utf8mb4 `
        "--result-file=$dumpFile" $localDb
    if ($LASTEXITCODE -ne 0) { throw "mysqldump failed (exit $LASTEXITCODE)." }

    # 2. Remove DEFINER clauses and sanitize long/multiline auto-generated VIEW column aliases
    Write-Host "[2/4] Membersihkan DEFINER & alias VIEW dari dump ..." -ForegroundColor Cyan
    $sql = [IO.File]::ReadAllText($dumpFile, [Text.Encoding]::UTF8)
    $sql = [Regex]::Replace($sql, 'DEFINER\s*=\s*`[^`]*`@`[^`]*`\s*', '')
    $aliasCounter = 0
    $sql = [Regex]::Replace($sql, '/\*!50001 VIEW[\s\S]*?\*/;', {
        param($viewMatch)
        [Regex]::Replace($viewMatch.Value, 'AS\s+`([^`]+)`', {
            param($m)
            $name = $m.Groups[1].Value
            if ($name.Length -gt 60 -or $name -match '[\r\n]') {
                $script:aliasCounter++
                return "AS ``col_$($script:aliasCounter)``"
            }
            return $m.Value
        })
    })
    [IO.File]::WriteAllText($dumpFile, $sql, (New-Object Text.UTF8Encoding $false))
    $sizeKb = [Math]::Round((Get-Item $dumpFile).Length / 1KB, 1)
    Write-Host "      Dump siap: $dumpFile ($sizeKb KB)"

    if ($ExportOnly) {
        Write-Host "ExportOnly: selesai tanpa mengubah Railway." -ForegroundColor Green
        return
    }

    if (-not $RailwayUrl) { throw 'Isi -RailwayUrl dengan nilai MYSQL_PUBLIC_URL dari service MySQL di Railway.' }

    $uri = [Uri]$RailwayUrl
    if ($uri.Scheme -ne 'mysql') { throw 'RailwayUrl harus diawali mysql://' }
    if ($uri.Host -like '*.railway.internal') {
        throw 'Itu MYSQL_PRIVATE_URL (hanya bisa dari dalam Railway). Pakai MYSQL_PUBLIC_URL (host *.proxy.rlwy.net).'
    }
    $userInfo = $uri.UserInfo.Split(':', 2)
    $remoteUser = [Uri]::UnescapeDataString($userInfo[0])
    $remotePassword = [Uri]::UnescapeDataString($userInfo[1])
    $remoteHost = $uri.Host
    $remotePort = if ($uri.Port -gt 0) { $uri.Port } else { 3306 }
    $remoteDb = $uri.AbsolutePath.TrimStart('/')
    if ($remoteDb -notmatch '^[A-Za-z0-9_]+$') { throw "Nama database Railway tidak valid: '$remoteDb'" }

    $remoteArgs = @("--host=$remoteHost", "--port=$remotePort", "--user=$remoteUser",
        '--get-server-public-key', '--default-character-set=utf8mb4')

    # 3. Reset Railway database (it currently holds an incomplete schema)
    Write-Host "[3/4] Reset database Railway '$remoteDb' di $($remoteHost):$remotePort ..." -ForegroundColor Yellow
    if (-not $Force) {
        $answer = Read-Host "Semua tabel di database Railway '$remoteDb' akan DIHAPUS lalu diganti isi '$localDb'. Ketik YA untuk lanjut"
        if ($answer -ne 'YA') { Write-Host 'Dibatalkan.'; return }
    }
    $env:MYSQL_PWD = $remotePassword
    & $mysql @remoteArgs "--execute=DROP DATABASE IF EXISTS $remoteDb; CREATE DATABASE $remoteDb CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
    if ($LASTEXITCODE -ne 0) { throw "Reset database Railway gagal (exit $LASTEXITCODE)." }

    # 4. Import dump into Railway
    Write-Host "[4/4] Import ke Railway (bisa 1-3 menit) ..." -ForegroundColor Cyan
    $process = Start-Process -FilePath $mysql -ArgumentList ($remoteArgs + @($remoteDb)) `
        -RedirectStandardInput $dumpFile -NoNewWindow -Wait -PassThru
    if ($process.ExitCode -ne 0) { throw "Import gagal (exit $($process.ExitCode))." }

    Write-Host 'Verifikasi data di Railway:' -ForegroundColor Green
    & $mysql @remoteArgs $remoteDb --table `
        "--execute=SELECT (SELECT COUNT(*) FROM siswa) AS siswa, (SELECT COUNT(*) FROM guru) AS guru, (SELECT COUNT(*) FROM users) AS users, (SELECT COUNT(*) FROM migrations) AS migrations, (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'sessions') AS sessions_table;"
    Write-Host 'Selesai. Redeploy / Restart service SINAR-CONNECT di Railway.' -ForegroundColor Green
}
finally {
    Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
}
