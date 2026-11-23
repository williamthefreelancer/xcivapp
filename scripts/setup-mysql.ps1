# Elevated MySQL 8.4 setup for local WordPress development
$ErrorActionPreference = "Continue"
$log = "C:\Users\johns\OneDrive\Desktop\super-ninety-four\mysql-setup.log"
Remove-Item $log -Force -ErrorAction SilentlyContinue
function Log($msg) {
  $line = "$(Get-Date -Format o) $msg"
  Add-Content -Path $log -Value $line
}

Log "Starting MySQL 8.4 setup"

$mysqlBin = "C:\Program Files\MySQL\MySQL Server 8.4\bin"
$basedir = "C:/Program Files/MySQL/MySQL Server 8.4"
$dataRoot = "C:\ProgramData\MySQL\MySQL Server 8.4"
$datadir = Join-Path $dataRoot "Data"
$iniPath = Join-Path $dataRoot "my.ini"
$uploads = Join-Path $dataRoot "Uploads"

New-Item -ItemType Directory -Force -Path $datadir, $uploads | Out-Null

$ini = @"
[client]
port=3306

[mysql]
no-beep

[mysqld]
port=3306
basedir="$basedir"
datadir="$($datadir -replace '\\','/')"
default-storage-engine=INNODB
sql-mode="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"
lower_case_table_names=1
secure-file-priv="$($uploads -replace '\\','/')"
max_connections=151
innodb_buffer_pool_size=128M
max_allowed_packet=64M
mysqlx_port=33060
"@
Set-Content -Path $iniPath -Value $ini -Encoding ASCII
Log "Wrote $iniPath"

foreach ($name in @("MySQL80", "MySQL84")) {
  $svc = Get-Service -Name $name -ErrorAction SilentlyContinue
  if ($svc) {
    if ($svc.Status -eq "Running") {
      Stop-Service $name -Force -ErrorAction SilentlyContinue
      Log "Stopped $name"
    }
    & sc.exe delete $name | Out-Null
    Start-Sleep -Seconds 2
    Log "Deleted $name service"
  }
}

# Clear incomplete data directory and re-initialize
$mysqlSystem = Join-Path $datadir "mysql"
if (-not (Test-Path $mysqlSystem)) {
  Log "Clearing and initializing data directory"
  Get-ChildItem $datadir -Force -ErrorAction SilentlyContinue | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
  $initOut = & "$mysqlBin\mysqld.exe" --defaults-file="$iniPath" --initialize-insecure 2>&1
  $initOut | ForEach-Object { Log "INIT: $_" }
  Log "Initialize exit code: $LASTEXITCODE"
} else {
  Log "Data directory already initialized"
}

if (-not (Test-Path $mysqlSystem)) {
  Log "ERROR: mysql system tables missing after initialize"
  exit 1
}

$installOut = & "$mysqlBin\mysqld.exe" --install MySQL84 --defaults-file="$iniPath" 2>&1
Log "Install: $installOut"
Start-Service MySQL84
Set-Service MySQL84 -StartupType Automatic
Start-Sleep -Seconds 5
$running = (Get-Service MySQL84).Status
Log "MySQL84 status: $running"

if ($running -ne "Running") {
  Log "ERROR: MySQL84 failed to start"
  exit 1
}

$rootPass = "WpLocal_Root_2026!"
$dbPass = "WpLocal_App_2026!"
$sqlFile = "C:\Users\johns\OneDrive\Desktop\super-ninety-four\scripts\wp-init.sql"
@"
ALTER USER 'root'@'localhost' IDENTIFIED BY '$rootPass';
CREATE DATABASE IF NOT EXISTS wordpress CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'wpuser'@'localhost' IDENTIFIED BY '$dbPass';
CREATE USER IF NOT EXISTS 'wpuser'@'127.0.0.1' IDENTIFIED BY '$dbPass';
GRANT ALL PRIVILEGES ON wordpress.* TO 'wpuser'@'localhost';
GRANT ALL PRIVILEGES ON wordpress.* TO 'wpuser'@'127.0.0.1';
FLUSH PRIVILEGES;
SELECT 'WP_DB_READY' AS status;
"@ | Set-Content -Path $sqlFile -Encoding ASCII

$mysql = "$mysqlBin\mysql.exe"
$out1 = & $mysql --protocol=TCP -h 127.0.0.1 -u root -e "source $sqlFile" 2>&1
Log "SQL1: $out1"
if ("$out1" -notmatch "WP_DB_READY") {
  $out2 = & $mysql --protocol=TCP -h 127.0.0.1 -u root "-p$rootPass" -e "source $sqlFile" 2>&1
  Log "SQL2: $out2"
}

@{
  root_password = $rootPass
  db_name = "wordpress"
  db_user = "wpuser"
  db_password = $dbPass
  db_host = "127.0.0.1"
  db_port = 3306
} | ConvertTo-Json | Set-Content -Path "C:\Users\johns\OneDrive\Desktop\super-ninety-four\.mysql-local.json" -Encoding UTF8

Log "MySQL setup complete"
