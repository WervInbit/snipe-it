[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $ExportPath,

    [Parameter(Mandatory = $true)]
    [string] $RuntimePath,

    [Parameter(Mandatory = $true)]
    [string] $AppImageDigest,

    [Parameter(Mandatory = $true)]
    [string] $WebImageDigest,

    [string] $AppImage = 'local/inbit-app',
    [string] $WebImage = 'local/inbit-web',
    [string] $ProjectName = 'snipeit-v1-data-rehearsal-20260820',
    [string] $DatabaseName = 'snipeit_rehearsal',
    [string] $DatabaseUser = 'snipeit_rehearsal',
    [string] $AppUrl = 'https://dev.inbit:18443',
    [int] $HttpPort = 18082,
    [int] $HttpsPort = 18443,
    [string] $BindAddress = '127.0.0.1',
    [string] $NetworkSubnet = '172.31.208.0/24',
    [string] $TlsCertificate = (Join-Path $PSScriptRoot '..\..\docker\certs\dev.inbit.crt'),
    [string] $TlsPrivateKey = (Join-Path $PSScriptRoot '..\..\docker\certs\dev.inbit-key.pem'),
    [switch] $Force
)

$ErrorActionPreference = 'Stop'

if ($ProjectName -notmatch '^snipeit-v1-data-rehearsal-[a-z0-9-]+$') {
    throw 'The Compose project name must use the dedicated rehearsal prefix.'
}

if ($DatabaseName -notmatch '^snipeit_rehearsal(?:_[a-z0-9_]+)?$') {
    throw 'The database name must use the dedicated snipeit_rehearsal prefix.'
}

foreach ($digest in @($AppImageDigest, $WebImageDigest)) {
    if ($digest -notmatch '^sha256:[0-9a-f]{64}$') {
        throw "Invalid image digest: $digest"
    }
}

$resolvedExport = (Resolve-Path -LiteralPath $ExportPath).Path
$legacyEnvironment = Join-Path $resolvedExport 'secrets\legacy.env'
$passportPrivate = Join-Path $resolvedExport 'secrets\oauth-private.key'
$passportPublic = Join-Path $resolvedExport 'secrets\oauth-public.key'

foreach ($requiredFile in @(
    (Join-Path $resolvedExport 'database.sql.gz'),
    (Join-Path $resolvedExport 'public-uploads.tar.gz'),
    (Join-Path $resolvedExport 'private-uploads.tar.gz'),
    (Join-Path $resolvedExport 'SHA256SUMS'),
    $legacyEnvironment,
    $passportPrivate,
    $passportPublic,
    $TlsCertificate,
    $TlsPrivateKey
)) {
    if (-not (Test-Path -LiteralPath $requiredFile -PathType Leaf)) {
        throw "Required rehearsal input is missing: $requiredFile"
    }
}

$appKeyLine = Get-Content -LiteralPath $legacyEnvironment |
    Where-Object { $_ -match '^APP_KEY=' } |
    Select-Object -First 1

if (-not $appKeyLine) {
    throw 'The legacy environment does not contain APP_KEY.'
}

$appKey = $appKeyLine.Substring('APP_KEY='.Length).Trim()
if (($appKey.StartsWith('"') -and $appKey.EndsWith('"')) -or
    ($appKey.StartsWith("'") -and $appKey.EndsWith("'"))) {
    $appKey = $appKey.Substring(1, $appKey.Length - 2)
}

if ([string]::IsNullOrWhiteSpace($appKey)) {
    throw 'The legacy APP_KEY is empty.'
}

function New-RandomSecret {
    param([int] $ByteCount = 48)

    $bytes = [byte[]]::new($ByteCount)
    [System.Security.Cryptography.RandomNumberGenerator]::Fill($bytes)

    return [Convert]::ToBase64String($bytes).TrimEnd('=').Replace('+', '-').Replace('/', '_')
}

function Write-Utf8Secret {
    param(
        [string] $Path,
        [AllowEmptyString()]
        [string] $Value
    )

    [System.IO.File]::WriteAllText(
        $Path,
        $Value + [Environment]::NewLine,
        [System.Text.UTF8Encoding]::new($false)
    )
}

$resolvedRuntime = [System.IO.Path]::GetFullPath($RuntimePath)
$secretsPath = Join-Path $resolvedRuntime 'secrets'
$tlsPath = Join-Path $resolvedRuntime 'tls'

$managedRuntimeFiles = @(
    (Join-Path $resolvedRuntime 'rehearsal.env'),
    (Join-Path $secretsPath 'app_key'),
    (Join-Path $secretsPath 'db_password'),
    (Join-Path $secretsPath 'db_root_password'),
    (Join-Path $secretsPath 'redis_password'),
    (Join-Path $secretsPath 'agent_api_token'),
    (Join-Path $secretsPath 'passport_private.pem'),
    (Join-Path $secretsPath 'passport_public.pem'),
    (Join-Path $tlsPath 'rehearsal.crt'),
    (Join-Path $tlsPath 'rehearsal.key')
)

$existingManagedFiles = @($managedRuntimeFiles | Where-Object {
    Test-Path -LiteralPath $_ -PathType Leaf
})

if ($existingManagedFiles.Count -gt 0 -and -not $Force) {
    throw 'The runtime path already contains managed files. Reuse the existing runtime or pass -Force to rotate its secrets explicitly.'
}

New-Item -ItemType Directory -Force -Path $resolvedRuntime, $secretsPath, $tlsPath | Out-Null

Write-Utf8Secret (Join-Path $secretsPath 'app_key') $appKey
Write-Utf8Secret (Join-Path $secretsPath 'db_password') (New-RandomSecret)
Write-Utf8Secret (Join-Path $secretsPath 'db_root_password') (New-RandomSecret)
Write-Utf8Secret (Join-Path $secretsPath 'redis_password') (New-RandomSecret)
Write-Utf8Secret (Join-Path $secretsPath 'agent_api_token') ''

Copy-Item -LiteralPath $passportPrivate -Destination (Join-Path $secretsPath 'passport_private.pem') -Force
Copy-Item -LiteralPath $passportPublic -Destination (Join-Path $secretsPath 'passport_public.pem') -Force
Copy-Item -LiteralPath $TlsCertificate -Destination (Join-Path $tlsPath 'rehearsal.crt') -Force
Copy-Item -LiteralPath $TlsPrivateKey -Destination (Join-Path $tlsPath 'rehearsal.key') -Force

function Convert-ToComposePath {
    param([string] $Path)

    return $Path.Replace('\', '/')
}

$suffix = $ProjectName.Substring('snipeit-v1-data-rehearsal-'.Length)
$environment = @(
    "COMPOSE_PROJECT_NAME=$ProjectName",
    "SNIPEIT_APP_IMAGE=$AppImage",
    "SNIPEIT_APP_IMAGE_DIGEST=$AppImageDigest",
    "SNIPEIT_WEB_IMAGE=$WebImage",
    "SNIPEIT_WEB_IMAGE_DIGEST=$WebImageDigest",
    "SNIPEIT_HTTP_PORT=$HttpPort",
    "SNIPEIT_HTTPS_PORT=$HttpsPort",
    "REHEARSAL_BIND_ADDRESS=$BindAddress",
    "SNIPEIT_PUBLIC_UPLOADS_VOLUME=${ProjectName}-public-uploads",
    "SNIPEIT_PRIVATE_UPLOADS_VOLUME=${ProjectName}-private-uploads",
    "SNIPEIT_BACKUPS_VOLUME=${ProjectName}-backups",
    "SNIPEIT_DB_VOLUME=${ProjectName}-database",
    "SNIPEIT_REDIS_VOLUME=${ProjectName}-redis",
    "SNIPEIT_TLS_VOLUME=${ProjectName}-tls",
    "APP_URL=$AppUrl",
    "APP_TRUSTED_PROXIES=$NetworkSubnet",
    'DB_CONNECTION=mysql',
    'DB_HOST=db',
    'DB_PORT=3306',
    "DB_DATABASE=$DatabaseName",
    "DB_USERNAME=$DatabaseUser",
    'DB_DUMP_PATH=/usr/bin',
    'DB_DUMP_SKIP_SSL=true',
    'REDIS_HOST=redis',
    'REDIS_PORT=6379',
    'REDIS_DATABASE=0',
    'LDAP_INTEGRATION_ENABLED=false',
    'MAIL_ENABLED=false',
    'AGENT_ALLOWED_IPS=',
    'AGENT_USER_ID=',
    'LOG_LEVEL=warning',
    "REHEARSAL_NETWORK_SUBNET=$NetworkSubnet",
    ('APP_KEY_FILE=' + (Convert-ToComposePath (Join-Path $secretsPath 'app_key'))),
    ('DB_PASSWORD_FILE=' + (Convert-ToComposePath (Join-Path $secretsPath 'db_password'))),
    ('DB_ROOT_PASSWORD_FILE=' + (Convert-ToComposePath (Join-Path $secretsPath 'db_root_password'))),
    ('REDIS_PASSWORD_FILE=' + (Convert-ToComposePath (Join-Path $secretsPath 'redis_password'))),
    ('AGENT_API_TOKEN_FILE=' + (Convert-ToComposePath (Join-Path $secretsPath 'agent_api_token'))),
    ('PASSPORT_PRIVATE_KEY_FILE=' + (Convert-ToComposePath (Join-Path $secretsPath 'passport_private.pem'))),
    ('PASSPORT_PUBLIC_KEY_FILE=' + (Convert-ToComposePath (Join-Path $secretsPath 'passport_public.pem'))),
    ('REHEARSAL_TLS_CERT_FILE=' + (Convert-ToComposePath (Join-Path $tlsPath 'rehearsal.crt'))),
    ('REHEARSAL_TLS_KEY_FILE=' + (Convert-ToComposePath (Join-Path $tlsPath 'rehearsal.key'))),
    ('REHEARSAL_EXPORT_PATH=' + (Convert-ToComposePath $resolvedExport)),
    "REHEARSAL_SUFFIX=$suffix"
)

[System.IO.File]::WriteAllLines(
    (Join-Path $resolvedRuntime 'rehearsal.env'),
    $environment,
    [System.Text.UTF8Encoding]::new($false)
)

[pscustomobject]@{
    RuntimePath = $resolvedRuntime
    EnvironmentFile = Join-Path $resolvedRuntime 'rehearsal.env'
    ProjectName = $ProjectName
    DatabaseName = $DatabaseName
    AppUrl = $AppUrl
    SecretsPrinted = $false
}
