param([string]$GuidePackageRoot = $PSScriptRoot)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
if ([string]::IsNullOrWhiteSpace($GuidePackageRoot)) {
    $GuidePackageRoot = Read-Host 'Paste the extracted Affinity guide package folder'
}
$GuidePackageRoot = [IO.Path]::GetFullPath($GuidePackageRoot.Trim().Trim('"'))
if (-not [IO.Directory]::Exists($GuidePackageRoot)) { throw 'Package folder not found.' }
$GuidePackagePrefix = $GuidePackageRoot.TrimEnd('\', '/') + [IO.Path]::DirectorySeparatorChar

function Resolve-GuidePackageFile([string]$RelativePath) {
    if ([IO.Path]::IsPathRooted($RelativePath) -or $RelativePath.Contains(':') -or
        (($RelativePath -split '[\\/]') -contains '..')) { throw "Unsafe package path: $RelativePath" }
    $resolved = [IO.Path]::GetFullPath([IO.Path]::Combine($GuidePackageRoot, $RelativePath))
    if (-not $resolved.StartsWith($GuidePackagePrefix, [StringComparison]::OrdinalIgnoreCase)) {
        throw "Package path escaped its folder: $RelativePath"
    }
    return $resolved
}

function Get-GuideHash([string]$FilePath) {
    return (Get-FileHash -LiteralPath $FilePath -Algorithm SHA256).Hash.ToLowerInvariant()
}

$checked = 0
foreach ($line in [IO.File]::ReadAllLines((Resolve-GuidePackageFile 'package-files.sha256'))) {
    if ([string]::IsNullOrWhiteSpace($line)) { continue }
    if ($line -notmatch '^([0-9a-f]{64})  (.+)$') { throw 'Invalid package checksum record.' }
    $expected = $Matches[1]
    $relative = $Matches[2]
    $filePath = Resolve-GuidePackageFile $relative
    if ((Get-GuideHash $filePath) -ne $expected) { throw "Package file changed: $relative" }
    $checked++
}
$inputManifest = [IO.File]::ReadAllText((Resolve-GuidePackageFile 'source/input-manifest.json')) | ConvertFrom-Json
if ($inputManifest.guides.Count -ne 25 -or $inputManifest.assets.Count -ne 82) {
    throw 'Expected exactly 25 guides and 82 original images.'
}
$assetByName = @{}
foreach ($asset in $inputManifest.assets) {
    $assetPath = Resolve-GuidePackageFile $asset.file
    $name = [IO.Path]::GetFileName($assetPath)
    if ($assetByName.ContainsKey($name)) { throw "Ambiguous image filename: $name" }
    $assetByName[$name] = $assetPath
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$stamp = [DateTime]::UtcNow.ToString('yyyyMMdd-HHmmss') + '-' + [Guid]::NewGuid().ToString('N').Substring(0, 8)
$workingRoot = Resolve-GuidePackageFile ('native-inputs/' + $stamp)
if ([IO.Directory]::Exists($workingRoot) -or [IO.File]::Exists($workingRoot)) {
    throw 'Existing working files preserved. Run again for a fresh folder.'
}
[void][IO.Directory]::CreateDirectory([IO.Path]::Combine($workingRoot, 'idml'))
$utf8 = New-Object Text.UTF8Encoding($false)
$newGuides = @()
try {
    foreach ($guide in $inputManifest.guides) {
        $sourcePath = Resolve-GuidePackageFile $guide.idml
        if ((Get-GuideHash $sourcePath) -ne $guide.sha256) { throw "IDML changed: $($guide.code)" }
        $targetPath = [IO.Path]::Combine($workingRoot, $guide.idml)
        $sourceZip = [IO.Compression.ZipFile]::OpenRead($sourcePath)
        # A stored mimetype entry is required. .NET Framework NoCompression
        # still uses ZIP method 8, so preserve a method-0 seed in Update mode.
        $seed = [Convert]::FromBase64String('UEsDBBQAAAAAADxtZV3Ldo+JKwAAACsAAAAIAAAAbWltZXR5cGVhcHBsaWNhdGlvbi92bmQuYWRvYmUuaW5kZXNpZ24taWRtbC1wYWNrYWdlUEsBAhQAFAAAAAAAPG1lXct2j4krAAAAKwAAAAgAAAAAAAAAAAAAAIABAAAAAG1pbWV0eXBlUEsFBgAAAAABAAEANgAAAFEAAAAAAA==')
        $targetFile = [IO.File]::Open($targetPath, [IO.FileMode]::CreateNew, [IO.FileAccess]::Write)
        try { $targetFile.Write($seed, 0, $seed.Length) } finally { $targetFile.Dispose() }
        $targetZip = [IO.Compression.ZipFile]::Open($targetPath, [IO.Compression.ZipArchiveMode]::Update)
        $links = 0
        try {
            foreach ($part in $sourceZip.Entries) {
                if ($part.FullName -eq 'mimetype') { continue }
                $level = [IO.Compression.CompressionLevel]::Optimal
                $newPart = $targetZip.CreateEntry($part.FullName, $level)
                $partInput = $part.Open()
                $partOutput = $newPart.Open()
                try {
                    if ($part.FullName -match '^(Spreads|MasterSpreads)/.*\.xml$') {
                        $readerSettings = New-Object Xml.XmlReaderSettings
                        $readerSettings.DtdProcessing = [Xml.DtdProcessing]::Prohibit
                        $readerSettings.XmlResolver = $null
                        $reader = [Xml.XmlReader]::Create($partInput, $readerSettings)
                        $document = New-Object Xml.XmlDocument
                        $document.PreserveWhitespace = $true
                        $document.XmlResolver = $null
                        try { $document.Load($reader) } finally { $reader.Dispose() }
                        foreach ($link in $document.SelectNodes('//*[local-name()="Link"]')) {
                            $uri = New-Object Uri($link.GetAttribute('LinkResourceURI'))
                            if (-not $uri.IsFile) { throw 'Unexpected non-file image link.' }
                            $assetName = [IO.Path]::GetFileName($uri.LocalPath)
                            if (-not $assetByName.ContainsKey($assetName)) { throw "Missing original image: $assetName" }
                            $replacementUri = New-Object Uri($assetByName[$assetName])
                            $link.SetAttribute('LinkResourceURI', $replacementUri.AbsoluteUri.Replace('file:///', 'file:/'))
                            $links++
                        }
                        $writerSettings = New-Object Xml.XmlWriterSettings
                        $writerSettings.Encoding = $utf8
                        $writerSettings.CloseOutput = $false
                        $writer = [Xml.XmlWriter]::Create($partOutput, $writerSettings)
                        try { $document.Save($writer) } finally { $writer.Dispose() }
                    } else {
                        $partInput.CopyTo($partOutput)
                    }
                } finally {
                    $partInput.Dispose()
                    $partOutput.Dispose()
                }
            }
        } finally {
            $targetZip.Dispose()
            $targetFile.Dispose()
            $sourceZip.Dispose()
        }
        if ($links -ne $guide.pictures) { throw "Image count changed: $($guide.code)" }
        $newGuides += [ordered]@{
            code = $guide.code; version = $guide.version; pages = $guide.pages
            idml = $guide.idml; bytes = ([IO.FileInfo]$targetPath).Length
            sha256 = Get-GuideHash $targetPath
            bodyTextFrames = $guide.bodyTextFrames; pictures = $guide.pictures
        }
    }
    $inputManifest.root = $GuidePackageRoot.Replace('\', '/')
    $inputManifest.idmlRoot = $workingRoot.Replace('\', '/')
    $inputManifest.inputSet = 'Portable corrected proposal; 25 guides / 68 pages; native proof and acceptance pending'
    $inputManifest.guides = @($newGuides)
    $literal = 'const INPUTS = ' + ($inputManifest | ConvertTo-Json -Depth 20) + ';'
    $template = [IO.File]::ReadAllText((Resolve-GuidePackageFile 'tools/conversion-template.js'))
    $startMarker = '/* INPUTS_START */'
    $endMarker = '/* INPUTS_END */'
    $start = $template.IndexOf($startMarker) + $startMarker.Length
    $end = $template.IndexOf($endMarker)
    if ($start -lt $startMarker.Length -or $end -le $start) { throw 'Invalid script template markers.' }
    $script = $template.Substring(0, $start) + "`n" + $literal + "`n" + $template.Substring($end)
    [IO.File]::WriteAllText([IO.Path]::Combine($workingRoot, 'input-manifest.json'), ($inputManifest | ConvertTo-Json -Depth 20), $utf8)
    [IO.File]::WriteAllText([IO.Path]::Combine($workingRoot, 'convert-all-25-guides.js'), $script, $utf8)
    $ready = 'Prepared copies only; Affinity has not run. Open the generated JS in the Affinity Script Editor. Start with the three-guide technical proof. Preserve every native edit. Inspect actual exports and resources.'
    [IO.File]::WriteAllText([IO.Path]::Combine($workingRoot, 'READY.txt'), $ready, $utf8)
    [pscustomobject]@{status = 'prepared'; folder = $workingRoot; packageFilesVerified = $checked; guides = 25; pages = 68; affinityExecuted = $false}
} catch {
    $failurePath = [IO.Path]::Combine($workingRoot, 'FAILED.txt')
    if (-not [IO.File]::Exists($failurePath)) { [IO.File]::WriteAllText($failurePath, $_.Exception.Message, $utf8) }
    throw
}
